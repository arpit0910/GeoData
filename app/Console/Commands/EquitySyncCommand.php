<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EquitySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'equities:sync {date?} {exchange?} {--force : Re-sync even if records already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Memory-optimized sync of daily Bhavcopy data from NSE and BSE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Environmental Optimizations
        ini_set('memory_limit', '1G');
        DB::connection()->disableQueryLog();

        $startDate = $this->argument('date') ?: now()->format('Y-m-d');
        $exchange = $this->argument('exchange');

        $currentDateObj = Carbon::parse($startDate);
        $attempts = 0;
        $maxAttempts = 10;

        $scriptPath = base_path('app/Scripts/fetch_bhavcopy.py');
        $pythonPath = $this->detectPython();

        while ($attempts < $maxAttempts) {
            $date = $currentDateObj->format('Y-m-d');
            $this->info("--- Checking sync for {$date} ---");

            // Skip if already synced (unless --force)
            if (!$this->option('force') && $this->isAlreadySynced($date, $exchange)) {
                $this->info("Records already exist for {$date}. Job complete.");
                return Command::SUCCESS;
            }

            // Attempt 1: Python Worker
            if ($pythonPath) {
                $pythonData = $this->fetchViaPython($pythonPath, $scriptPath, $date, $exchange);
                if ($pythonData) {
                    $this->info("Successfully fetched data via Python worker.");
                    return $this->processData($pythonData, $date);
                }
            }

            // Attempt 2: PHP Native Fallback
            $phpData = $this->handlePhpFetch($date, $exchange);
            if (!empty($phpData)) {
                $this->info("Successfully fetched data via PHP native fetcher.");
                return $this->processData($phpData, $date);
            }

            $this->warn("No data found for {$date}. Stepping back 1 day...");
            $currentDateObj->subDay();
            $attempts++;
        }

        $this->error("Failed to find any data after {$maxAttempts} attempts.");
        return Command::FAILURE;
    }

    /**
     * Internal: Process, Calculate, and Upsert data in batches
     */
    protected function processData($data, $date)
    {
        $this->info("Starting memory-efficient processing for " . count($data) . " records...");
        $now = now();
        $dateObj = Carbon::parse($date);

        // Step 1: Sync the base Equity table (names, symbols)
        $this->syncEquities($data, $now);

        // Step 2: Map ISIN to IDs (Chunked to prevent SQL limit errors)
        $isins = collect($data)->pluck('isin')->unique();
        $isinToId = collect();
        foreach ($isins->chunk(1000) as $chunk) {
            $isinToId = $isinToId->merge(Equity::whereIn('isin', $chunk)->pluck('id', 'isin'));
        }

        // Step 3: Get historical dates for performance window calculations
        $periodConfig = $this->getHistoricalDateWindows($dateObj);
        $targetDates = collect($periodConfig['window_map'])->flatten()->filter()->unique()->values()->toArray();

        // Step 4: Batch Process ISINs (Crucial for Memory)
        // We group data by ISIN and process 200 stocks at a time
        $isinGroups = collect($data)->groupBy('isin');
        $chunks = $isinGroups->chunk(200);

        foreach ($chunks as $index => $isinBatch) {
            $batchIsins = $isinBatch->keys();
            $batchIds = $isinToId->only($batchIsins)->values();

            // Fetch historical prices ONLY for this batch
            $historicalBatch = DB::table('equity_prices')
                ->whereIn('traded_date', $targetDates)
                ->whereIn('equity_id', $batchIds)
                ->select('equity_id', 'traded_date', 'nse_close', 'bse_close')
                ->get()
                ->groupBy('equity_id');

            // Fetch existing prices for today ONLY for this batch (additive sync)
            $existingPricesBatch = EquityPrice::whereIn('isin', $batchIsins)
                ->where('traded_date', $date)
                ->get()
                ->keyBy('isin');

            $upsertData = [];

            foreach ($isinBatch as $isin => $records) {
                $equityId = $isinToId[$isin] ?? null;
                if (!$equityId) continue;

                $upsertData[] = $this->calculateMetrics(
                    $equityId,
                    $isin,
                    $date,
                    $now,
                    $records->where('exchange', 'NSE')->first(),
                    $records->where('exchange', 'BSE')->first(),
                    $existingPricesBatch->get($isin),
                    $historicalBatch->get($equityId),
                    $periodConfig['window_map']
                );
            }

            // Perform bulk upsert for this batch
            if (!empty($upsertData)) {
                EquityPrice::upsert($upsertData, ['isin', 'traded_date'], $this->getUpsertColumns());
            }

            $this->info("Processed batch " . ($index + 1) . " / " . $chunks->count());

            // Clean up loop variables to free memory
            unset($historicalBatch, $existingPricesBatch, $upsertData);
        }

        $this->info("Sync completed successfully for {$date}.");
        return Command::SUCCESS;
    }

    /**
     * Logic: Merge data from NSE/BSE and calculate performance %
     */
    protected function calculateMetrics($equityId, $isin, $date, $now, $nse, $bse, $existing, $history, $windowMap)
    {
        // Null-safe price extraction
        $nse_close = (float)($nse['close'] ?? ($existing->nse_close ?? 0));
        $bse_close = (float)($bse['close'] ?? ($existing->bse_close ?? 0));

        $record = [
            'equity_id' => $equityId,
            'isin' => $isin,
            'traded_date' => $date,
            'nse_open' => (float)($nse['open'] ?? ($existing->nse_open ?? 0)),
            'nse_high' => (float)($nse['high'] ?? ($existing->nse_high ?? 0)),
            'nse_low' => (float)($nse['low'] ?? ($existing->nse_low ?? 0)),
            'nse_close' => $nse_close,
            'nse_prev_close' => (float)($nse['prev_close'] ?? ($existing->nse_prev_close ?? 0)),
            'nse_volume' => (int)($nse['volume'] ?? ($existing->nse_volume ?? 0)),
            'bse_open' => (float)($bse['open'] ?? ($existing->bse_open ?? 0)),
            'bse_high' => (float)($bse['high'] ?? ($existing->bse_high ?? 0)),
            'bse_low' => (float)($bse['low'] ?? ($existing->bse_low ?? 0)),
            'bse_close' => $bse_close,
            'bse_prev_close' => (float)($bse['prev_close'] ?? ($existing->bse_prev_close ?? 0)),
            'bse_volume' => (int)($bse['volume'] ?? ($existing->bse_volume ?? 0)),
            'spread' => ($nse_close > 0 && $bse_close > 0) ? abs($nse_close - $bse_close) : 0,
            'created_at' => $existing ? $existing->created_at : $now,
            'updated_at' => $now,
        ];

        // Process Historical Returns
        if ($history) {
            $historyByDate = $history->keyBy('traded_date');
            foreach (['1d', '3d', '7d', '1m', '3m', '6m', '1y', '3y'] as $period) {
                foreach ($windowMap[$period] as $wd) {
                    if ($prev = $historyByDate->get($wd)) {
                        if (isset($prev->nse_close) && $prev->nse_close > 0 && $nse_close > 0) {
                            $record["nse_chg_{$period}"] = (($nse_close - $prev->nse_close) / $prev->nse_close) * 100;
                            $record["nse_val_{$period}"] = $prev->nse_close;
                        }
                        if (isset($prev->bse_close) && $prev->bse_close > 0 && $bse_close > 0) {
                            $record["bse_chg_{$period}"] = (($bse_close - $prev->bse_close) / $prev->bse_close) * 100;
                            $record["bse_val_{$period}"] = $prev->bse_close;
                        }
                        break; // Stop at the closest available date in the window
                    }
                }
            }
        }
        return $record;
    }

    /**
     * Logic: Sync Equity Names and Symbols safely
     */
    protected function syncEquities($data, $now)
    {
        $isins = collect($data)->pluck('isin')->unique();
        $existing = Equity::whereIn('isin', $isins)->get()->keyBy('isin');

        $equities = collect($data)->groupBy('isin')->map(function ($group, $isin) use ($existing, $now) {
            $nse = $group->where('exchange', 'NSE')->first();
            $bse = $group->where('exchange', 'BSE')->first();
            $ext = $existing->get($isin);

            $name = ($nse['name'] ?? null) ?: ($bse['name'] ?? null) ?: ($ext->company_name ?? ($nse['symbol'] ?? ($bse['symbol'] ?? 'Unknown')));

            return [
                'isin' => $isin,
                'company_name' => $name,
                'nse_symbol' => ($nse['symbol'] ?? null) ?: ($ext->nse_symbol ?? null),
                'bse_symbol' => ($bse['symbol'] ?? null) ?: ($ext->bse_symbol ?? null),
                'is_active' => true,
                'created_at' => $ext ? $ext->created_at : $now,
                'updated_at' => $now,
            ];
        })->values()->chunk(500);

        foreach ($equities as $chunk) {
            Equity::upsert($chunk->toArray(), ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'updated_at']);
        }
    }

    /**
     * Fetcher: Executes python script
     */
    protected function fetchViaPython($pythonPath, $scriptPath, $date, $exchange)
    {
        $output = [];
        $returnVar = 0;
        $exchangeParam = $exchange ?: "";

        exec("{$pythonPath} \"{$scriptPath}\" {$date} {$exchangeParam} 2>&1", $output, $returnVar);

        $jsonData = '';
        foreach ($output as $line) {
            if (strpos(trim($line), '[') === 0) {
                $jsonData = $line;
                break;
            }
        }

        return !empty($jsonData) ? json_decode($jsonData, true) : null;
    }

    /**
     * Fetcher: PHP Native logic
     */
    protected function handlePhpFetch($date, $exchange)
    {
        $data = [];
        $dateObj = Carbon::parse($date);

        if (!$exchange || strtolower($exchange) === 'nse') {
            $nseData = $this->fetchNseData($dateObj);
            if ($nseData) $data = array_merge($data, $nseData);
        }

        if (!$exchange || strtolower($exchange) === 'bse') {
            $bseData = $this->fetchBseData($dateObj);
            if ($bseData) $data = array_merge($data, $bseData);
        }

        return $data;
    }

    protected function fetchNseData($dateObj)
    {
        $dateUnderscore = $dateObj->format('Ymd');
        $month = strtoupper($dateObj->format('M'));
        $year = $dateObj->format('Y');
        $dateTrad = strtoupper($dateObj->format('dMY'));
        $date = $dateObj->format('Y-m-d');

        $urls = [
            "https://nsearchives.nseindia.com/content/cm/BhavCopy_NSE_CM_0_0_0_{$dateUnderscore}_F_0000.csv.zip",
            "https://archives.nseindia.com/content/historical/EQUITIES/{$year}/{$month}/cm{$dateTrad}bhav.csv.zip"
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.nseindia.com/all-reports'
                ])->timeout(30)->withoutVerifying()->get($url);

                if ($response->successful() && strlen($response->body()) > 500) {
                    Storage::put("equities/bhavcopies/{$date}/NSE_" . basename($url), $response->body());
                    return $this->parseFileContent($response->body(), 'NSE', $url);
                }
            } catch (\Exception $e) {
            }
        }
        return null;
    }

    protected function fetchBseData($dateObj)
    {
        $dateUnderscore = $dateObj->format('Ymd');
        $date = $dateObj->format('Y-m-d');
        $url = "https://www.bseindia.com/download/BhavCopy/Equity/BhavCopy_BSE_CM_0_0_0_{$dateUnderscore}_F_0000.CSV";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.bseindia.com/markets/MarketInfo/BhavCopy.aspx'
            ])->timeout(30)->withoutVerifying()->get($url);

            if ($response->successful() && strlen($response->body()) > 500) {
                Storage::put("equities/bhavcopies/{$date}/BSE_" . basename($url), $response->body());
                return $this->parseFileContent($response->body(), 'BSE', $url);
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    protected function parseFileContent($content, $exchange, $url)
    {
        if (str_ends_with(strtolower($url), '.zip')) {
            $tempFile = tempnam(sys_get_temp_dir(), 'bhav');
            file_put_contents($tempFile, $content);
            $zip = new \ZipArchive();
            $records = [];
            if ($zip->open($tempFile) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if (str_ends_with(strtolower($zip->getNameIndex($i)), '.csv')) {
                        $records = array_merge($records, $this->parseCsvString($zip->getFromIndex($i), $exchange));
                    }
                }
                $zip->close();
            }
            unlink($tempFile);
            return $records;
        }
        return $this->parseCsvString($content, $exchange);
    }

    /**
     * CSV Optimization: Stream-based reading
     */
    protected function parseCsvString($csvString, $exchange)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvString);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers) return [];
        $headers = array_map('trim', $headers);

        $map = [
            'isin' => ['ISIN', 'FinInstrmId', 'ISIN_CODE'],
            'symbol' => ['TckrSymb', 'SYMBOL', 'SC_NAME'],
            'name' => ['FinInstrmNm', 'COMPANY_NAME', 'FULL_NAME'],
            'open' => ['OpnPric', 'OPEN', 'OPEN_PRC'],
            'high' => ['HghPric', 'HIGH', 'HIGH_PRC'],
            'low' => ['LwPric', 'LOW', 'LOW_PRC'],
            'close' => ['ClsPric', 'CLOSE', 'CLOSE_PRC', 'LAST_PRC'],
            'prev' => ['PrvsClsgPric', 'PREVCLOSE', 'PREV_CLOSE'],
            'volume' => ['TtlTradgVol', 'TOTTRDQTY', 'NO_SHARES', 'TRADE_QTY'],
        ];

        $records = [];
        while (($row = fgetcsv($stream)) !== false) {
            $count = min(count($headers), count($row));
            $rowMerged = array_combine(array_slice($headers, 0, $count), array_slice($row, 0, $count));

            $mapped = [];
            foreach ($map as $key => $candidates) {
                foreach ($candidates as $cand) {
                    if (isset($rowMerged[$cand])) {
                        $mapped[$key] = trim($rowMerged[$cand]);
                        break;
                    }
                }
            }

            if (!empty($mapped['isin'])) {
                $records[] = array_merge($mapped, [
                    'open' => (float)($mapped['open'] ?? 0),
                    'high' => (float)($mapped['high'] ?? 0),
                    'low' => (float)($mapped['low'] ?? 0),
                    'close' => (float)($mapped['close'] ?? 0),
                    'prev_close' => (float)($mapped['prev'] ?? 0),
                    'volume' => (int)($mapped['volume'] ?? 0),
                    'exchange' => $exchange
                ]);
            }
        }
        fclose($stream);
        return $records;
    }

    protected function getHistoricalDateWindows($dateObj)
    {
        $periods = ['1d' => 1, '3d' => 3, '7d' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '1y' => 365, '3y' => 1095];
        $windowMap = [];
        $allTradingDates = EquityPrice::where('traded_date', '<', $dateObj->format('Y-m-d'))
            ->orderBy('traded_date', 'desc')
            ->limit(1200)->pluck('traded_date');

        foreach ($periods as $label => $days) {
            $target = $dateObj->copy()->subDays($days);
            $windowMap[$label] = $allTradingDates->filter(fn($d) => abs(Carbon::parse($d)->diffInDays($target)) <= 7)
                ->sortBy(fn($d) => abs(Carbon::parse($d)->diffInDays($target)))->values()->toArray();
        }
        return ['window_map' => $windowMap];
    }

    protected function getUpsertColumns()
    {
        return [
            'nse_open',
            'nse_high',
            'nse_low',
            'nse_close',
            'nse_prev_close',
            'nse_volume',
            'bse_open',
            'bse_high',
            'bse_low',
            'bse_close',
            'bse_prev_close',
            'bse_volume',
            'nse_chg_1d',
            'nse_chg_3d',
            'nse_chg_7d',
            'nse_chg_1m',
            'nse_chg_3m',
            'nse_chg_6m',
            'nse_chg_1y',
            'nse_chg_3y',
            'bse_chg_1d',
            'bse_chg_3d',
            'bse_chg_7d',
            'bse_chg_1m',
            'bse_chg_3m',
            'bse_chg_6m',
            'bse_chg_1y',
            'bse_chg_3y',
            'spread',
            'updated_at'
        ];
    }

    protected function detectPython()
    {
        if (!function_exists('exec')) return null;
        foreach (['python3', 'python', 'py'] as $path) {
            exec("{$path} --version 2>&1", $out, $ret);
            if ($ret === 0) return $path;
        }
        return null;
    }

    protected function isAlreadySynced($date, $exchange)
    {
        $exchangeArg = strtolower($exchange ?? '');
        $hasNse = EquityPrice::where('traded_date', $date)->where('nse_close', '>', 0)->exists();
        $hasBse = EquityPrice::where('traded_date', $date)->where('bse_close', '>', 0)->exists();
        return match ($exchangeArg) {
            'nse' => $hasNse,
            'bse' => $hasBse,
            default => $hasNse && $hasBse,
        };
    }
}
