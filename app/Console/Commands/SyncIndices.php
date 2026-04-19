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
    protected $signature = 'indices:sync {date?} {exchange?} {--analytics-only : Skip fetch, just recalculate analytics for existing data}';

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
        $startDate = $this->argument('date') ?: now()->format('Y-m-d');
        $exchange  = strtoupper($this->argument('exchange') ?? '');
        $currentDateObj = Carbon::parse($startDate);

        // Analytics-only mode: skip fetching, recalculate on existing data
        if ($this->option('analytics-only')) {
            $dateStr = $currentDateObj->format('Y-m-d');
            $exists = IndexPrice::where('traded_date', $dateStr)->exists();
            if (!$exists) {
                $this->warn("No data found for {$dateStr}, skipping analytics.");
                return Command::SUCCESS;
            }
            $this->info("Recalculating analytics for {$currentDateObj->format('d/m/Y')}...");
            $this->calculateAnalytics($currentDateObj);
            return Command::SUCCESS;
        }

        $attempts    = 0;
        $maxAttempts = 10;

        while ($attempts < $maxAttempts) {
            $dateStr = $currentDateObj->format('Y-m-d');
            $this->info("Checking sync for indices on: {$currentDateObj->format('d/m/Y')}");

            $query = IndexPrice::where('traded_date', $dateStr);
            if ($exchange) {
                $query->whereHas('index', function($q) use ($exchange) {
                    $q->where('exchange', $exchange);
                });
            }

            if ($query->exists()) {
                $this->info("Indices data already exists for {$currentDateObj->format('d/m/Y')}. Job complete.");
                return Command::SUCCESS;
            }

            $syncedNse = 0;
            $syncedBse = 0;

            try {
                if (!$exchange || $exchange === 'NSE') {
                    $syncedNse = $this->syncNiftyIndices($currentDateObj);
                }

                if (!$exchange || $exchange === 'BSE') {
                    $syncedBse = $this->syncBseIndices($currentDateObj);
                }

                if ($syncedNse > 0 || $syncedBse > 0) {
                    $this->calculateAnalytics($currentDateObj);
                    $this->info("Sync completed successfully for {$dateStr}.");
                    return Command::SUCCESS;
                }
            } catch (\Exception $e) {
                $this->error("Error during sync for {$dateStr}: " . $e->getMessage());
            }

            $this->warn("No data available for {$dateStr}. Trying previous day...");
            $currentDateObj->subDay();
            $attempts++;
        }

        $this->error("Failed to find any data after {$maxAttempts} attempts.");
        return Command::FAILURE;
    }

    private function calculateAnalytics(Carbon $date): void
    {
        $this->info("Calculating analytical metrics for indices...");

        $prices = IndexPrice::where('traded_date', $date->format('Y-m-d'))->get();

        if ($prices->isEmpty()) {
            $this->warn("No prices found for {$date->format('d/m/Y')} to calculate analytics.");
            return;
        }

        // Exact calendar targets — subMonths/subYears are precise, subDays(270) is not 9 months
        $periodCalendarTargets = [
            '1d' => $date->copy()->subDay(),
            '3d' => $date->copy()->subDays(3),
            '7d' => $date->copy()->subDays(7),
            '1m' => $date->copy()->subMonth(),
            '3m' => $date->copy()->subMonths(3),
            '6m' => $date->copy()->subMonths(6),
            '9m' => $date->copy()->subMonths(9),
            '1y' => $date->copy()->subYear(),
            '3y' => $date->copy()->subYears(3),
        ];

        // Fetch all trading dates in the relevant range as Carbon objects
        $oldestTarget = $date->copy()->subYears(3)->subDays(15)->format('Y-m-d');
        $tradingDates = IndexPrice::where('traded_date', '<', $date->format('Y-m-d'))
            ->where('traded_date', '>=', $oldestTarget)
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->pluck('traded_date')
            ->map(fn($d) => Carbon::parse($d instanceof Carbon ? $d->format('Y-m-d') : (string)$d));

        // For each period build a window of ±10 calendar days around the target,
        // sorted closest-first so each index uses the most accurate available date
        $dateWindowMap = [];
        foreach ($periodCalendarTargets as $period => $target) {
            $dateWindowMap[$period] = $tradingDates
                ->filter(fn($d) => abs($d->diffInDays($target)) <= 10)
                ->sortBy(fn($d) => abs($d->diffInDays($target)))
                ->map(fn($d) => $d->format('Y-m-d'))
                ->values()
                ->toArray();
        }

        $allTargetDates = collect($dateWindowMap)->flatten()->filter()->unique()->values()->toArray();

        // Single bulk fetch — no per-index queries in the loop below
        $historicalData = IndexPrice::whereIn('traded_date', $allTargetDates)
            ->get()
            ->groupBy('index_code');

        foreach ($prices as $price) {
            $code    = $price->index_code;
            $history = $historicalData->get($code);
            $historyByDate = $history
                ? $history->keyBy(fn($item) => $item->traded_date instanceof Carbon ? $item->traded_date->format('Y-m-d') : (string)$item->traded_date)
                : null;

            // 0. Auto-fill prev_close from the 1d window if missing
            if (!$price->prev_close && $historyByDate) {
                foreach ($dateWindowMap['1d'] as $pd) {
                    $last = $historyByDate->get($pd);
                    if ($last && $last->close > 0) { $price->prev_close = $last->close; break; }
                }
            }

            // 1. Core analytics
            if ($price->prev_close && $price->prev_close > 0) {
                if ($price->open) {
                    $price->gap_pct = (($price->open - $price->prev_close) / $price->prev_close) * 100;
                }
                $price->range_pct = (($price->high - $price->low) / $price->prev_close) * 100;
            }
            if ($price->open && $price->open > 0) {
                $price->intraday_chg_pct = (($price->close - $price->open) / $price->open) * 100;
            }

            // 2. Historical returns — walk window closest-first, stop at first hit
            if ($historyByDate) {
                foreach ($dateWindowMap as $key => $candidates) {
                    foreach ($candidates as $candidateDate) {
                        $pastPrice = $historyByDate->get($candidateDate);
                        if ($pastPrice && $pastPrice->close > 0) {
                            $price->{"val_{$key}"} = $pastPrice->close;
                            if ($price->close > 0) {
                                $price->{"chg_{$key}"} = (($price->close - $pastPrice->close) / $pastPrice->close) * 100;
                            }
                            break;
                        }
                    }
                }
            }

            $price->save();
        }
    }

    private function getHistoricalReturn(string $code, Carbon $currentDate, int $daysAgo): ?float
    {
        // Keep this method for backward compatibility if needed, but it's no longer used in calculateAnalytics
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

    private function syncNiftyIndices(Carbon $date): int
    {
        // URL Pattern: https://www.niftyindices.com/Daily_Snapshot/ind_close_all_DDMMYYYY.csv
        $url = "https://www.niftyindices.com/Daily_Snapshot/ind_close_all_" . $date->format('dmY') . ".csv";

        $this->info("Fetching NSE data from: {$url}");

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.niftyindices.com/reports/daily-reports',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            ])->timeout(30)->get($url);

            if ($response->failed()) {
                throw new \Exception("HTTP request failed with status " . $response->status());
            }

            $csvData = $response->body();

            // Check if we got HTML instead of CSV (common when redirected to error pages)
            if (
                str_contains(strtolower($response->header('Content-Type')), 'text/html') ||
                str_starts_with(trim($csvData), '<!DOCTYPE') ||
                str_starts_with(trim($csvData), '<html')
            ) {
                throw new \Exception("URL returned HTML instead of CSV. Market might be closed or archive unavailable.");
            }

            // Remove UTF-8 BOM if present
            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);

            $lines = explode("\n", str_replace("\r", "", trim($csvData)));
            if (empty($lines)) return 0;

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
                return 0;
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
            return count($pricesData);
        } catch (\Exception $e) {
            $this->warn("NSE sync failed: " . $e->getMessage());
            return 0;
        }
    }

    private function syncBseIndices(Carbon $date): int
    {
        $urls = [
            // Current format (since 2025)
            "https://www.bseindia.com/Downloads/AllIndices/AllIndices_" . $date->format('dmY') . ".csv",
            // Historic zip format (2022-2024) — dmy = 2-digit year e.g. 010123
            "https://www.bseindia.com/download/BhavCopy/Index/IndexBhavCopy_" . $date->format('dmy') . ".zip",
            // BSE Summary CSV variations
            "https://www.bseindia.com/bsedata/Index_Bhavcopy/INDEXSummary_" . $date->format('dmY') . ".csv",
            "https://www.bseindia.com/bsedata/Index_Bhavcopy/INDEXSummary_" . $date->format('dmy') . ".csv",
            // Other historic patterns
            "https://www.bseindia.com/Downloads/MarketInfo/Indices_" . $date->format('dmy') . ".zip",
            "https://www.bseindia.com/download/BhavCopy/Index/indexbhavcopy" . $date->format('Ymd') . ".csv",
            "https://www.bseindia.com/download/BhavCopy/Index/Indexbhavcopy" . $date->format('Ymd') . ".csv",
            "https://www.bseindia.com/download/BhavCopy/Index/indexbhavcopy_" . $date->format('Ymd') . ".csv",
            "https://www.bseindia.com/download/allindices/allindices_" . $date->format('dmY') . ".csv",
        ];

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer'    => 'https://www.bseindia.com/markets/MarketInfo/DispMarkInfoStat.aspx',
            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        ];

        $warmupPages = [
            'https://www.bseindia.com/markets/MarketInfo/DispMarkInfoStat.aspx',
            'https://www.bseindia.com/markets/equity/EQReports/BhavCopy.aspx',
        ];

        $csvData    = null;
        $successUrl = null;
        $timeouts   = [15, 30, 60];

        foreach ($timeouts as $attempt => $timeout) {
            if ($attempt > 0) {
                $this->warn("  BSE retry attempt {$attempt} with {$timeout}s timeout...");
                sleep(3);
            }

            // Fresh cookie jar + warmup on every attempt (different page each time)
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            try {
                Http::withOptions(['cookies' => $cookieJar])
                    ->withHeaders($headers)
                    ->timeout(10)
                    ->get($warmupPages[$attempt % count($warmupPages)]);
            } catch (\Exception $e) {
                // Warmup failure is non-fatal
            }

            foreach ($urls as $url) {
                $this->info("Fetching BSE data from: {$url} (timeout: {$timeout}s)");
                try {
                    $response = Http::withOptions(['cookies' => $cookieJar])
                        ->withHeaders($headers)
                        ->timeout($timeout)
                        ->get($url);

                    if ($response->successful()) {
                        $body = $response->body();
                        if (
                            !str_contains(strtolower($response->header('Content-Type')), 'text/html') &&
                            !str_starts_with(trim($body), '<!DOCTYPE') &&
                            !str_starts_with(trim($body), '<html')
                        ) {
                            $csvData    = $body;
                            $successUrl = $url;
                            break 2; // break out of both loops
                        } else {
                            $this->warn("  Received HTML instead of data from $url");
                        }
                    } else {
                        $this->warn("  HTTP {$response->status()} for $url");
                    }
                } catch (\Exception $e) {
                    $this->warn("  Exception for $url: " . $e->getMessage());
                }
            }
        }

        if (!$csvData) {
            $this->warn("Could not fetch BSE bulk data for {$date->format('d/m/Y')} after all retries. Falling back to Yahoo Finance...");
            return $this->syncBseViaYahoo($date);
        }

        $this->info("Successfully fetched BSE data from: $successUrl");

        try {
            $isZip = str_ends_with(strtolower($successUrl), '.zip');
            if ($isZip) {
                if (!class_exists('ZipArchive')) {
                    $this->error("PHP ZipArchive class not found.");
                    return 0;
                }
                $tempFile = tempnam(sys_get_temp_dir(), 'bse_idx');
                file_put_contents($tempFile, $csvData);
                $zip = new \ZipArchive();
                $csvData = '';
                if ($zip->open($tempFile) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (str_ends_with(strtolower($filename), '.csv')) {
                            $csvData = $zip->getFromIndex($i);
                            break;
                        }
                    }
                    $zip->close();
                }
                unlink($tempFile);
                if (empty($csvData)) {
                    $this->warn("No CSV file found in BSE ZIP archive.");
                    return 0;
                }
            }

            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);
            $lines = explode("\n", str_replace("\r", "", trim($csvData)));

            if (empty($lines)) return 0;

            $header = str_getcsv(array_shift($lines));
            $map = array_flip(array_map('trim', $header));

            // Support multiple column name variations (BSE changes these often)
            // BSE changed column names across formats — cover all known variations
            $colMap = [
                'name'     => ['Index Name', 'INDEX NAME', 'Index_Name', 'IndexName', 'I_name'],
                'open'     => ['Open', 'OPEN', 'OPEN_INDEX_VAL', 'Opening', 'I_open'],
                'high'     => ['High', 'HIGH', 'HIGH_INDEX_VAL', 'Highest', 'I_high'],
                'low'      => ['Low', 'LOW', 'LOW_INDEX_VAL', 'Lowest', 'I_low'],
                'close'    => ['Close', 'CLOSE', 'CLOSING_INDEX_VAL', 'Closing', 'I_close'],
                'prev'     => ['Prev_Close', 'PREV_CLOSE', 'Previous Close', 'PREVCLOSE'],
                'change'   => ['% Change', 'Chg %', 'Percentage Change', 'PERCENTAGE_CHANGE', 'ChgPer'],
                'vol'      => ['Volume', 'Total Volume', 'VOLUME', 'TRADE_QTY'],
                'turnover' => ['Turnover', 'Turnover Cr', 'TURNOVER', 'NET_TURNOV'],
                'pe'       => ['PE', 'P/E', 'PE_RATIO', 'I_pe'],
                'pb'       => ['PB', 'P/B', 'PB_RATIO', 'I_pb'],
                'yield'    => ['Yield', 'Div Yield', 'DY', 'DIV_YIELD', 'I_yl'],
            ];

            // Resolve actual index column names from the CSV header
            $resolvedMap = [];
            foreach ($colMap as $key => $candidates) {
                foreach ($candidates as $candidate) {
                    if (isset($map[$candidate])) {
                        $resolvedMap[$key] = $map[$candidate];
                        break;
                    }
                }
            }

            if (!isset($resolvedMap['name'])) {
                $this->warn("BSE 'Index Name' column not found in CSV. Headers: " . implode(', ', $header));
                return 0;
            }

            $indicesData = [];
            $pricesData = [];
            $now = now();

            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < count($header)) continue;

                $rawName = trim($row[$resolvedMap['name']]);
                $code = "BSE_" . str_replace([' ', '&', '(', ')'], '_', strtoupper($rawName));

                $indicesData[] = [
                    'index_code' => $code,
                    'index_name' => $rawName,
                    'exchange'   => 'BSE',
                    'category'   => $this->guessCategory($rawName),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $pricesData[] = [
                    'index_code'     => $code,
                    'traded_date'    => $date->format('Y-m-d'),
                    'open'           => $this->parseFloat($row[$resolvedMap['open'] ?? -1] ?? 0),
                    'high'           => $this->parseFloat($row[$resolvedMap['high'] ?? -1] ?? 0),
                    'low'            => $this->parseFloat($row[$resolvedMap['low'] ?? -1] ?? 0),
                    'close'          => $this->parseFloat($row[$resolvedMap['close'] ?? -1] ?? 0),
                    'prev_close'     => $this->parseFloat($row[$resolvedMap['prev'] ?? -1] ?? 0),
                    'change_percent' => $this->parseFloat($row[$resolvedMap['change'] ?? -1] ?? 0),
                    'volume'         => $this->parseFloat($row[$resolvedMap['vol'] ?? -1] ?? 0),
                    'turnover'       => $this->parseFloat($row[$resolvedMap['turnover'] ?? -1] ?? 0),
                    'pe_ratio'       => $this->parseFloat($row[$resolvedMap['pe'] ?? -1] ?? 0),
                    'pb_ratio'       => $this->parseFloat($row[$resolvedMap['pb'] ?? -1] ?? 0),
                    'div_yield'      => $this->parseFloat($row[$resolvedMap['yield'] ?? -1] ?? 0),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (empty($indicesData)) return 0;

            Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);

            // Chunk upsert for prices to avoid large statement issues
            foreach (array_chunk($pricesData, 100) as $chunk) {
                IndexPrice::upsert($chunk, ['index_code', 'traded_date'], [
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
            }

            $this->info("Processed " . count($pricesData) . " BSE indices from $successUrl.");
            return count($pricesData);
        } catch (\Exception $e) {
            $this->warn("BSE processing failed: " . $e->getMessage());
            return 0;
        }
    }

    private function syncBseViaYahoo(Carbon $date): int
    {
        // BSE indices available on Yahoo Finance — used as fallback when BSE bulk download fails
        $tickers = [
            '^BSESN'          => ['name' => 'S&P BSE SENSEX',           'code' => 'BSE_SENSEX',    'cat' => 'Broad-based'],
            'BSE-100.BO'      => ['name' => 'S&P BSE 100',               'code' => 'BSE_100',       'cat' => 'Broad-based'],
            'BSE-200.BO'      => ['name' => 'S&P BSE 200',               'code' => 'BSE_200',       'cat' => 'Broad-based'],
            'BSE-500.BO'      => ['name' => 'S&P BSE 500',               'code' => 'BSE_500',       'cat' => 'Broad-based'],
            'BSE-MidCap.BO'   => ['name' => 'S&P BSE MidCap',            'code' => 'BSE_MIDCAP',    'cat' => 'Broad-based'],
            'BSE-SmlCap.BO'   => ['name' => 'S&P BSE SmallCap',          'code' => 'BSE_SMALLCAP',  'cat' => 'Broad-based'],
            'BSE-LargeCap.BO' => ['name' => 'S&P BSE LargeCap',          'code' => 'BSE_LARGECAP',  'cat' => 'Broad-based'],
            'BSE-BANK.BO'     => ['name' => 'S&P BSE BANKEX',            'code' => 'BSE_BANKEX',    'cat' => 'Sectoral'],
            'BSE-IT.BO'       => ['name' => 'S&P BSE IT',                'code' => 'BSE_IT',        'cat' => 'Sectoral'],
            'BSE-AUTO.BO'     => ['name' => 'S&P BSE AUTO',              'code' => 'BSE_AUTO',      'cat' => 'Sectoral'],
            'BSE-FMCG.BO'     => ['name' => 'S&P BSE FMCG',              'code' => 'BSE_FMCG',      'cat' => 'Sectoral'],
            'BSE-HC.BO'       => ['name' => 'S&P BSE Healthcare',        'code' => 'BSE_HC',        'cat' => 'Sectoral'],
            'BSE-METAL.BO'    => ['name' => 'S&P BSE Metal',             'code' => 'BSE_METAL',     'cat' => 'Sectoral'],
            'BSE-OIL.BO'      => ['name' => 'S&P BSE Oil & Gas',         'code' => 'BSE_OIL',       'cat' => 'Sectoral'],
            'BSE-PWR.BO'      => ['name' => 'S&P BSE Power',             'code' => 'BSE_PWR',       'cat' => 'Sectoral'],
            'BSE-TECK.BO'     => ['name' => 'S&P BSE Teck',              'code' => 'BSE_TECK',      'cat' => 'Sectoral'],
            'BSE-CD.BO'       => ['name' => 'S&P BSE Consumer Durables', 'code' => 'BSE_CD',        'cat' => 'Sectoral'],
            'BSE-CG.BO'       => ['name' => 'S&P BSE Capital Goods',     'code' => 'BSE_CG',        'cat' => 'Sectoral'],
            'BSE-Realty.BO'   => ['name' => 'S&P BSE Realty',            'code' => 'BSE_REALTY',    'cat' => 'Sectoral'],
            'BSEALLCAP.BO'    => ['name' => 'S&P BSE AllCap',            'code' => 'BSE_ALLCAP',    'cat' => 'Broad-based'],
        ];

        $pricesData = [];
        $indicesData = [];
        $now = now();
        $dateStr = $date->format('Y-m-d');

        $timestampStart = $date->copy()->startOfDay()->timestamp;
        $timestampEnd = $date->copy()->endOfDay()->timestamp;

        foreach ($tickers as $ticker => $meta) {
            try {
                $this->info("  Fetching {$meta['name']} from Yahoo Finance...");
                // Use v8 chart API which is more stable for single-day JSON requests
                $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$timestampStart}&period2={$timestampEnd}&interval=1d";

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0'
                ])->timeout(10)->get($url);

                if ($response->successful()) {
                    $json = $response->json();
                    $result = $json['chart']['result'][0] ?? null;
                    if (!$result || empty($result['timestamp'])) continue;

                    $quote = $result['indicators']['quote'][0] ?? null;
                    if (!$quote || empty($quote['close'][0])) continue;

                    $meta2      = $result['meta'] ?? [];
                    $prevClose  = isset($meta2['chartPreviousClose']) ? (float)$meta2['chartPreviousClose'] : null;
                    $closeVal   = (float)($quote['close'][0] ?? 0);
                    $changePct  = ($prevClose && $prevClose > 0)
                        ? (($closeVal - $prevClose) / $prevClose) * 100
                        : null;

                    $indicesData[] = [
                        'index_code' => $meta['code'],
                        'index_name' => $meta['name'],
                        'exchange'   => 'BSE',
                        'category'   => $meta['cat'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $pricesData[] = [
                        'index_code'     => $meta['code'],
                        'traded_date'    => $dateStr,
                        'open'           => (float)($quote['open'][0] ?? 0),
                        'high'           => (float)($quote['high'][0] ?? 0),
                        'low'            => (float)($quote['low'][0] ?? 0),
                        'close'          => $closeVal,
                        'prev_close'     => $prevClose,
                        'change_percent' => $changePct,
                        'volume'         => (float)($quote['volume'][0] ?? 0),
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            } catch (\Exception $e) {
                $this->warn("  Yahoo fetch failed for $ticker: " . $e->getMessage());
            }
        }

        if (!empty($indicesData)) {
            Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
            IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
                'open',
                'high',
                'low',
                'close',
                'prev_close',
                'change_percent',
                'volume',
                'updated_at'
            ]);
            $this->info("  Successfully populated " . count($pricesData) . " indices via Yahoo Finance.");
            return count($pricesData);
        } else {
            $this->warn("  No BSE data found on Yahoo Finance for {$dateStr}.");
            return 0;
        }
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
