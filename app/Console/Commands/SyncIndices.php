<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Index;
use App\Models\IndexPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncIndices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:sync {date?} {--exchange= : Specify exchange (NSE or BSE)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync daily stock market indices performance from NSE/BSE';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateStr = $this->argument('date') ?: now()->subDay()->format('Y-m-d');
        $date = Carbon::parse($dateStr);

        $this->info("Starting sync for indices on: {$date->format('d/m/Y')}");

        try {
            $exchange = strtoupper($this->option('exchange'));

            if (!$exchange || $exchange === 'NSE') {
                $this->syncNiftyIndices($date);
            }

            if (!$exchange || $exchange === 'BSE') {
                $this->syncBseIndices($date);
            }

            $this->calculateAnalytics($date);

            $this->info("Sync completed successfully.");
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            Log::error("SyncIndices failed: " . $e->getMessage(), [
                'date' => $dateStr,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    private function calculateAnalytics(Carbon $date): void
    {
        $this->info("Calculating analytical metrics for indices...");

        $prices = IndexPrice::where('traded_date', $date->format('Y-m-d'))->get();

        foreach ($prices as $price) {
            $code = $price->index_code;

            // 0. Auto-fill prev_close if missing (important for NSE data which doesn't always provide it in the daily snapshot)
            if (!$price->prev_close) {
                $lastPrice = IndexPrice::where('index_code', $code)
                    ->where('traded_date', '<', $date->format('Y-m-d'))
                    ->orderBy('traded_date', 'desc')
                    ->first();
                if ($lastPrice) {
                    $price->prev_close = $lastPrice->close;
                }
            }

            // 1. Core Analytics (Gap, Intraday, Range)
            if ($price->prev_close && $price->prev_close > 0) {
                if ($price->open) {
                    $price->gap_pct = (($price->open - $price->prev_close) / $price->prev_close) * 100;
                }
                $price->range_pct = (($price->high - $price->low) / $price->prev_close) * 100;
            }
            if ($price->open && $price->open > 0) {
                $price->intraday_chg_pct = (($price->close - $price->open) / $price->open) * 100;
            }

            // 2. Historical Returns
            $price->chg_1d = $this->getHistoricalReturn($code, $date, 1);
            $price->chg_3d = $this->getHistoricalReturn($code, $date, 3);
            $price->chg_7d = $this->getHistoricalReturn($code, $date, 7);
            $price->chg_1m = $this->getHistoricalReturn($code, $date, 30);
            $price->chg_3m = $this->getHistoricalReturn($code, $date, 90);
            $price->chg_6m = $this->getHistoricalReturn($code, $date, 180);
            $price->chg_1y = $this->getHistoricalReturn($code, $date, 365);
            $price->chg_3y = $this->getHistoricalReturn($code, $date, 1095);

            $price->save();
        }
    }

    private function getHistoricalReturn(string $code, Carbon $currentDate, int $daysAgo): ?float
    {
        $targetDate = $currentDate->copy()->subDays($daysAgo);

        $pastPrice = IndexPrice::where('index_code', $code)
            ->where('traded_date', '<=', $targetDate->format('Y-m-d'))
            ->orderBy('traded_date', 'desc')
            ->first();

        if ($pastPrice && $pastPrice->close > 0) {
            $currentPrice = IndexPrice::where('index_code', $code)
                ->where('traded_date', $currentDate->format('Y-m-d'))
                ->first();

            if ($currentPrice && $currentPrice->close > 0) {
                return (($currentPrice->close - $pastPrice->close) / $pastPrice->close) * 100;
            }
        }

        return null;
    }

    private function syncNiftyIndices(Carbon $date): void
    {
        // URL Pattern: https://www.niftyindices.com/Daily_Snapshot/ind_close_all_DDMMYYYY.csv
        $url = "https://www.niftyindices.com/Daily_Snapshot/ind_close_all_" . $date->format('dmY') . ".csv";

        $this->info("Fetching NSE data from: {$url}");

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Referer' => 'https://www.niftyindices.com/reports/daily-reports',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        ])->get($url);

        if ($response->failed()) {
            throw new \Exception("Could not fetch NSE data from URL: {$url}. Exchange might be closed or URL format changed.");
        }

        $csvData = $response->body();
        // Remove UTF-8 BOM if present
        $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);

        $lines = explode("\n", str_replace("\r", "", trim($csvData)));
        $header = str_getcsv(array_shift($lines));

        // Map headers to column indexes dynamically
        $map = array_flip(array_map('trim', $header));

        if (!isset($map['Index Name'])) {
            $available = implode(', ', array_keys($map));
            throw new \Exception("Required column 'Index Name' not found in CSV. Available columns: [{$available}]");
        }

        $indicesData = [];
        $pricesData = [];
        $now = now();

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < count($header)) continue;

            $rawName = trim($row[$map['Index Name']]);
            $code = str_replace([' ', '&', '(', ')'], '_', strtoupper($rawName));

            $indicesData[] = [
                'index_code' => $code,
                'index_name' => $rawName,
                'exchange'   => 'NSE',
                'category'   => $this->guessCategory($rawName),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $pricesData[] = [
                'index_code'     => $code,
                'traded_date'    => $date->format('Y-m-d'),
                'open'           => $this->parseFloat($row[$map['Open Index Value'] ?? $map['Open'] ?? 0]),
                'high'           => $this->parseFloat($row[$map['High Index Value'] ?? $map['High'] ?? 0]),
                'low'            => $this->parseFloat($row[$map['Low Index Value'] ?? $map['Low'] ?? 0]),
                'close'          => $this->parseFloat($row[$map['Closing Index Value'] ?? $map['Close'] ?? 0]),
                'prev_close'     => null,
                'change_percent' => $this->parseFloat($row[$map['Change(%)'] ?? $map['Percent Change'] ?? 0]),
                'volume'         => $this->parseFloat($row[$map['Volume'] ?? 0]),
                'turnover'       => $this->parseFloat($row[$map['Turnover (Rs. Cr.)'] ?? $map['Turnover'] ?? 0]),
                'pe_ratio'       => $this->parseFloat($row[$map['P/E'] ?? 0]),
                'pb_ratio'       => $this->parseFloat($row[$map['P/B'] ?? 0]),
                'div_yield'      => $this->parseFloat($row[$map['Div Yield'] ?? 0]),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (empty($indicesData)) {
            $this->warn("No valid rows found in CSV.");
            return;
        }

        // Batch Update Master List
        Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);

        // Batch Update Prices
        IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
            'open',
            'high',
            'low',
            'close',
            'prev_close',
            'change_percent',
            'volume',
            'turnover',
            'pe_ratio',
            'pb_ratio',
            'div_yield',
            'updated_at'
        ]);

        $this->info("Processed " . count($pricesData) . " NSE indices.");
    }

    private function syncBseIndices(Carbon $date): void
    {
        // URL Pattern: https://www.bseindia.com/Downloads/AllIndices/AllIndices_ddmmyyyy.csv
        $url = "https://www.bseindia.com/Downloads/AllIndices/AllIndices_" . $date->format('dmY') . ".csv";

        $this->info("Fetching BSE data from: {$url}");

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Referer' => 'https://www.bseindia.com/markets/MarketInfo/DispMarkInfoStat.aspx',
        ])->get($url);

        if ($response->failed()) {
            $this->warn("Could not fetch BSE data (might be unavailable for this date). Skipping.");
            return;
        }

        $csvData = $response->body();
        $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);

        $lines = explode("\n", str_replace("\r", "", trim($csvData)));
        $header = str_getcsv(array_shift($lines));
        $map = array_flip(array_map('trim', $header));

        if (!isset($map['Index Name'])) {
            $this->warn("BSE 'Index Name' column not found. Skipping BSE sync.");
            return;
        }

        $indicesData = [];
        $pricesData = [];
        $now = now();

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < count($header)) continue;

            $rawName = trim($row[$map['Index Name']]);
            $code = "BSE_" . str_replace([' ', '&', '(', ')'], '_', strtoupper($rawName));

            $indicesData[] = [
                'index_code' => $code,
                'index_name' => $rawName,
                'exchange'   => 'BSE',
                'category'   => $this->guessCategory($rawName),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // BSE headers often use 'Prev_Close' instead of points change
            $pricesData[] = [
                'index_code'     => $code,
                'traded_date'    => $date->format('Y-m-d'),
                'open'           => $this->parseFloat($row[$map['Open'] ?? 0]),
                'high'           => $this->parseFloat($row[$map['High'] ?? 0]),
                'low'            => $this->parseFloat($row[$map['Low'] ?? 0]),
                'close'          => $this->parseFloat($row[$map['Close'] ?? 0]),
                'prev_close'     => $this->parseFloat($row[$map['Prev_Close'] ?? $map['Previous Close'] ?? 0]),
                'change_percent' => $this->parseFloat($row[$map['% Change'] ?? $map['Chg %'] ?? 0]),
                'volume'         => $this->parseFloat($row[$map['Volume'] ?? $map['Total Volume'] ?? 0]),
                'turnover'       => $this->parseFloat($row[$map['Turnover'] ?? $map['Turnover Cr'] ?? 0]),
                'pe_ratio'       => $this->parseFloat($row[$map['PE'] ?? $map['P/E'] ?? 0]),
                'pb_ratio'       => $this->parseFloat($row[$map['PB'] ?? $map['P/B'] ?? 0]),
                'div_yield'      => $this->parseFloat($row[$map['Yield'] ?? $map['Div Yield'] ?? 0]),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (empty($indicesData)) return;

        Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
        IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
            'open',
            'high',
            'low',
            'close',
            'prev_close',
            'change_percent',
            'volume',
            'turnover',
            'pe_ratio',
            'pb_ratio',
            'div_yield',
            'updated_at'
        ]);

        $this->info("Processed " . count($pricesData) . " BSE indices.");
    }

    private function guessCategory(string $name): string
    {
        $name = strtolower($name);
        if (str_contains($name, 'sectoral') || str_contains($name, 'bank') || str_contains($name, 'it') || str_contains($name, 'auto')) {
            return 'Sectoral';
        }
        if (str_contains($name, 'nifty 50') || str_contains($name, 'nifty 100') || str_contains($name, 'next 50')) {
            return 'Broad-based';
        }
        return 'Thematic';
    }

    private function parseFloat($value): ?float
    {
        $clean = str_replace([',', ' '], '', $value);
        return is_numeric($clean) ? (float) $clean : null;
    }

    private function calculateChange($close, $prevClose): float
    {
        $c = $this->parseFloat($close);
        $p = $this->parseFloat($prevClose);
        if ($p && $p > 0) {
            return (($c - $p) / $p) * 100;
        }
        return 0;
    }
}
