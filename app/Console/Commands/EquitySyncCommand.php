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

    protected $description = 'Memory-optimized sync of daily Bhavcopy data from NSE and BSE';

    protected bool $shouldStop = false;

    public function handle(): int
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);
        DB::connection()->disableQueryLog();
        $this->setupSignals();

        $startDate  = $this->argument('date') ?: now()->format('Y-m-d');
        $exchange   = $this->argument('exchange');
        $startedAt  = microtime(true);

        Log::info('[equities:sync] started', [
            'date'     => $startDate,
            'exchange' => $exchange,
            'force'    => $this->option('force'),
        ]);

        $currentDateObj = Carbon::parse($startDate);
        $attempts   = 0;
        $maxAttempts = 10;
        $scriptPath = base_path('app/Scripts/fetch_bhavcopy.py');
        $pythonPath = $this->detectPython();

        while ($attempts < $maxAttempts) {
            if ($this->shouldStop) {
                $this->warn('Stop signal — aborting date search.');
                Log::warning('[equities:sync] stopped by signal');
                return Command::FAILURE;
            }

            $date = $currentDateObj->format('Y-m-d');
            $this->info("--- Checking sync for {$date} ---");
            Log::info('[equities:sync] checking date', ['date' => $date, 'attempt' => $attempts + 1]);

            if (!$this->option('force') && $this->isAlreadySynced($date, $exchange)) {
                $this->info("Records already exist for {$date}. Job complete.");
                Log::info('[equities:sync] already synced', ['date' => $date]);
                return Command::SUCCESS;
            }

            // Attempt 1: Python Worker
            if ($pythonPath) {
                $t = microtime(true);
                $pythonData = $this->fetchViaPython($pythonPath, $scriptPath, $date, $exchange);
                if ($pythonData) {
                    $this->info(sprintf('Fetched %d records via Python (%.1fs).', count($pythonData), microtime(true) - $t));
                    Log::info('[equities:sync] python fetch success', ['date' => $date, 'records' => count($pythonData)]);
                    return $this->processData($pythonData, $date);
                }
                $this->warn("Python worker returned no data for {$date} — falling back to PHP.");
                Log::warning('[equities:sync] python fetch empty', ['date' => $date]);
            }

            // Attempt 2: PHP Native Fallback
            $t = microtime(true);
            $phpData = $this->handlePhpFetch($date, $exchange);
            if (!empty($phpData)) {
                $this->info(sprintf('Fetched %d records via PHP (%.1fs).', count($phpData), microtime(true) - $t));
                Log::info('[equities:sync] php fetch success', ['date' => $date, 'records' => count($phpData)]);
                return $this->processData($phpData, $date);
            }

            $this->warn("No data found for {$date}. Stepping back 1 day...");
            Log::warning('[equities:sync] no data, stepping back', ['date' => $date]);
            $currentDateObj->subDay();
            $attempts++;
        }

        $elapsed = round(microtime(true) - $startedAt, 1);
        $this->error("Failed to find any data after {$maxAttempts} attempts ({$elapsed}s).");
        Log::error('[equities:sync] exhausted all attempts', [
            'start_date'  => $startDate,
            'exchange'    => $exchange,
            'max_attempts'=> $maxAttempts,
            'elapsed_s'   => $elapsed,
        ]);
        return Command::FAILURE;
    }

    /**
     * Internal: Process, Calculate, and Upsert data in batches
     */
    protected function processData($data, $date)
    {
        $data = collect($data)
            ->filter(fn($row) => is_array($row) && isset($row['isin']) && is_scalar($row['isin']) && trim((string) $row['isin']) !== '')
            ->map(function ($row) {
                $row['isin'] = trim((string) $row['isin']);
                return $row;
            })
            ->values()
            ->all();

        if (empty($data)) {
            $this->warn("No valid equity records found after filtering malformed ISIN rows.");
            return Command::SUCCESS;
        }

        $processStart = microtime(true);
        $this->info(sprintf('[equities:sync] Processing %d records for %s...', count($data), $date));
        Log::info('[equities:sync] processData started', ['date' => $date, 'records' => count($data)]);

        $now     = now();
        $dateObj = Carbon::parse($date);

        // Step 1: Sync the base Equity table (names, symbols)
        $t = microtime(true);
        $this->syncEquities($data, $now);
        Log::info('[equities:sync] syncEquities done', ['elapsed_ms' => round((microtime(true) - $t) * 1000)]);

        // Step 2: Map ISIN to IDs
        $isins    = collect($data)->pluck('isin')->unique();
        $isinToId = collect();
        foreach ($isins->chunk(1000) as $chunk) {
            $isinToId = $isinToId->merge(Equity::whereIn('isin', $chunk)->pluck('id', 'isin'));
        }

        // Step 3: Historical date windows for period returns
        $t = microtime(true);
        $periodConfig = $this->getHistoricalDateWindows($dateObj);
        $targetDates  = collect($periodConfig['window_map'])->flatten()->filter()->unique()->values()->toArray();
        Log::info('[equities:sync] date windows built', [
            'target_dates' => count($targetDates),
            'elapsed_ms'   => round((microtime(true) - $t) * 1000),
        ]);

        // Step 4: Batch Process ISINs in groups of 200
        $isinGroups = collect($data)->groupBy('isin');
        $chunks     = $isinGroups->chunk(200);
        $totalChunks = $chunks->count();

        foreach ($chunks as $index => $isinBatch) {
            if ($this->shouldStop) {
                $this->warn('Stop signal — aborting after batch ' . $index . '/' . $totalChunks . '.');
                Log::warning('[equities:sync] stopped by signal in processData', ['batch' => $index, 'total' => $totalChunks]);
                break;
            }

            $t = microtime(true);
            $batchIsins      = $isinBatch->keys()->filter(fn($isin) => is_scalar($isin) && trim((string)$isin) !== '')->map(fn($isin) => (string)$isin)->values();
            $batchIsinsArray = $batchIsins->all();
            $batchIds        = $isinToId->only($batchIsinsArray)->values()->filter(fn($id) => is_scalar($id))->all();

            $historicalBatch = DB::table('equity_prices')
                ->whereIn('traded_date', $targetDates)
                ->whereIn('equity_id', $batchIds)
                ->select('equity_id', 'traded_date', 'nse_close', 'bse_close')
                ->get()
                ->groupBy('equity_id');

            $existingPricesBatch = EquityPrice::whereIn('isin', $batchIsinsArray)
                ->where('traded_date', $date)
                ->get()
                ->keyBy(fn($item) => (string)$item->isin);

            $upsertData = [];
            foreach ($isinBatch as $isin => $records) {
                if (!is_scalar($isin)) continue;
                $isin     = (string)$isin;
                $equityId = $isinToId->get($isin);
                if (!$equityId) continue;

                $upsertData[] = $this->calculateMetrics(
                    $equityId, $isin, $date, $now,
                    $records->where('exchange', 'NSE')->first(),
                    $records->where('exchange', 'BSE')->first(),
                    $existingPricesBatch->get($isin),
                    $historicalBatch->get($equityId),
                    $periodConfig['window_map']
                );
            }

            if (!empty($upsertData)) {
                EquityPrice::upsert($upsertData, ['isin', 'traded_date'], $this->getUpsertColumns());
            }

            $batchMs = round((microtime(true) - $t) * 1000);
            $this->info(sprintf('  Batch %d/%d — %d records (%dms)', $index + 1, $totalChunks, count($upsertData), $batchMs));
            Log::info('[equities:sync] batch done', [
                'date'       => $date,
                'batch'      => $index + 1,
                'total'      => $totalChunks,
                'upserted'   => count($upsertData),
                'elapsed_ms' => $batchMs,
            ]);

            unset($historicalBatch, $existingPricesBatch, $upsertData);
        }

        $elapsed = round(microtime(true) - $processStart, 1);
        $this->info("Sync completed for {$date} ({$elapsed}s).");
        Log::info('[equities:sync] processData complete', ['date' => $date, 'elapsed_s' => $elapsed]);

        return Command::SUCCESS;
    }

    /**
     * Logic: Merge data from NSE/BSE and calculate performance %
     */
    protected function calculateMetrics($equityId, $isin, $date, $now, $nse, $bse, $existing, $history, $windowMap)
    {
        // Null-safe price extraction
        $nse_open       = (float)($nse['open']       ?? ($existing->nse_open       ?? 0));
        $nse_high       = (float)($nse['high']       ?? ($existing->nse_high       ?? 0));
        $nse_low        = (float)($nse['low']        ?? ($existing->nse_low        ?? 0));
        $nse_close      = (float)($nse['close']      ?? ($existing->nse_close      ?? 0));
        $nse_prev_close = (float)($nse['prev_close'] ?? ($existing->nse_prev_close ?? 0));
        $nse_turnover   = (float)($nse['turnover']   ?? ($existing->nse_turnover   ?? 0));
        $nse_trades     = (int)  ($nse['trades']     ?? ($existing->nse_trades     ?? 0));

        $bse_open       = (float)($bse['open']       ?? ($existing->bse_open       ?? 0));
        $bse_high       = (float)($bse['high']       ?? ($existing->bse_high       ?? 0));
        $bse_low        = (float)($bse['low']        ?? ($existing->bse_low        ?? 0));
        $bse_close      = (float)($bse['close']      ?? ($existing->bse_close      ?? 0));
        $bse_prev_close = (float)($bse['prev_close'] ?? ($existing->bse_prev_close ?? 0));
        $bse_turnover   = (float)($bse['turnover']   ?? ($existing->bse_turnover   ?? 0));
        $bse_trades     = (int)  ($bse['trades']     ?? ($existing->bse_trades     ?? 0));

        // Intraday metrics (same logic as equities:update-metrics)
        $nse_gap_pct          = $nse_prev_close > 0 ? (($nse_open  - $nse_prev_close) / $nse_prev_close) * 100 : null;
        $nse_range_pct        = $nse_prev_close > 0 ? (($nse_high  - $nse_low)        / $nse_prev_close) * 100 : null;
        $nse_intraday_chg_pct = $nse_open       > 0 ? (($nse_close - $nse_open)       / $nse_open)       * 100 : null;
        $nse_avg_ticket_size  = $nse_trades      > 0 ? $nse_turnover / $nse_trades                             : null;

        $bse_gap_pct          = $bse_prev_close > 0 ? (($bse_open  - $bse_prev_close) / $bse_prev_close) * 100 : null;
        $bse_range_pct        = $bse_prev_close > 0 ? (($bse_high  - $bse_low)        / $bse_prev_close) * 100 : null;
        $bse_intraday_chg_pct = $bse_open       > 0 ? (($bse_close - $bse_open)       / $bse_open)       * 100 : null;
        $bse_avg_ticket_size  = $bse_trades      > 0 ? $bse_turnover / $bse_trades                             : null;

        $record = [
            'equity_id'            => $equityId,
            'isin'                 => $isin,
            'traded_date'          => $date,
            'nse_open'             => $nse_open,
            'nse_high'             => $nse_high,
            'nse_low'              => $nse_low,
            'nse_close'            => $nse_close,
            'nse_prev_close'       => $nse_prev_close,
            'nse_volume'           => (int)($nse['volume']    ?? ($existing->nse_volume    ?? 0)),
            'nse_turnover'         => $nse_turnover,
            'nse_trades'           => $nse_trades,
            'nse_avg_price'        => (float)($nse['avg_price'] ?? ($existing->nse_avg_price ?? 0)),
            'bse_open'             => $bse_open,
            'bse_high'             => $bse_high,
            'bse_low'              => $bse_low,
            'bse_close'            => $bse_close,
            'bse_prev_close'       => $bse_prev_close,
            'bse_volume'           => (int)($bse['volume']    ?? ($existing->bse_volume    ?? 0)),
            'bse_turnover'         => $bse_turnover,
            'bse_trades'           => $bse_trades,
            'bse_avg_price'        => (float)($bse['avg_price'] ?? ($existing->bse_avg_price ?? 0)),
            'spread'               => ($nse_close > 0 && $bse_close > 0) ? abs($nse_close - $bse_close) : 0,
            'nse_gap_pct'          => $nse_gap_pct,
            'nse_range_pct'        => $nse_range_pct,
            'nse_intraday_chg_pct' => $nse_intraday_chg_pct,
            'nse_avg_ticket_size'  => $nse_avg_ticket_size,
            'bse_gap_pct'          => $bse_gap_pct,
            'bse_range_pct'        => $bse_range_pct,
            'bse_intraday_chg_pct' => $bse_intraday_chg_pct,
            'bse_avg_ticket_size'  => $bse_avg_ticket_size,
            'created_at'           => $existing ? $existing->created_at : $now,
            'updated_at'           => $now,
            // Pre-initialize all period fields so every row has the same shape for upsert
            'nse_chg_1d' => null, 'nse_val_1d' => null,
            'nse_chg_3d' => null, 'nse_val_3d' => null,
            'nse_chg_7d' => null, 'nse_val_7d' => null,
            'nse_chg_1m' => null, 'nse_val_1m' => null,
            'nse_chg_3m' => null, 'nse_val_3m' => null,
            'nse_chg_6m' => null, 'nse_val_6m' => null,
            'nse_chg_9m' => null, 'nse_val_9m' => null,
            'nse_chg_1y' => null, 'nse_val_1y' => null,
            'nse_chg_3y' => null, 'nse_val_3y' => null,
            'bse_chg_1d' => null, 'bse_val_1d' => null,
            'bse_chg_3d' => null, 'bse_val_3d' => null,
            'bse_chg_7d' => null, 'bse_val_7d' => null,
            'bse_chg_1m' => null, 'bse_val_1m' => null,
            'bse_chg_3m' => null, 'bse_val_3m' => null,
            'bse_chg_6m' => null, 'bse_val_6m' => null,
            'bse_chg_9m' => null, 'bse_val_9m' => null,
            'bse_chg_1y' => null, 'bse_val_1y' => null,
            'bse_chg_3y' => null, 'bse_val_3y' => null,
        ];

        // Process Historical Returns
        if ($history) {
            $historyByDate = $history->keyBy('traded_date');
            foreach (['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'] as $period) {
                foreach ($windowMap[$period] ?? [] as $wd) {
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
        $isins = collect($data)
            ->pluck('isin')
            ->filter(fn($isin) => is_scalar($isin) && trim((string) $isin) !== '')
            ->map(fn($isin) => (string) $isin)
            ->unique()
            ->values();

        $existing = Equity::whereIn('isin', $isins->all())->get()->keyBy(fn($item) => (string) $item->isin);

        $equities = collect($data)->groupBy('isin')->map(function ($group, $isin) use ($existing, $now) {
            if (!is_scalar($isin)) {
                return null;
            }

            $isin = (string) $isin;
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
        })->filter()->values()->chunk(500);

        foreach ($equities as $chunk) {
            Equity::upsert($chunk->toArray(), ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'updated_at']);
        }
    }

    /**
     * Fetcher: Executes python script
     */
    protected function fetchViaPython($pythonPath, $scriptPath, $date, $exchange)
    {
        if (!file_exists($scriptPath)) {
            Log::warning('equities:sync python script not found', ['script_path' => $scriptPath]);
            return null;
        }

        $output = [];
        $returnVar = 0;
        $exchangeParam = $exchange ? escapeshellarg($exchange) : "";
        $cmd = escapeshellarg($pythonPath) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($date) . ' ' . $exchangeParam . ' 2>&1';

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warning('equities:sync python worker failed', [
                'date' => $date,
                'exchange' => $exchange,
                'python_path' => $pythonPath,
                'script_path' => $scriptPath,
                'exit_code' => $returnVar,
                'output' => $output,
            ]);
        }

        $jsonData = '';
        foreach ($output as $line) {
            if (strpos(trim($line), '[') === 0) {
                $jsonData = $line;
                break;
            }
        }

        if (empty($jsonData)) {
            if (!empty($output)) {
                Log::warning('equities:sync python worker produced no JSON payload', [
                    'date' => $date,
                    'exchange' => $exchange,
                    'output' => $output,
                ]);
            }

            return null;
        }

        $decoded = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('equities:sync python worker returned invalid JSON', [
                'date' => $date,
                'exchange' => $exchange,
                'json_error' => json_last_error_msg(),
                'payload_sample' => substr($jsonData, 0, 500),
            ]);
            return null;
        }

        return $decoded;
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
                Log::warning('equities:sync NSE fetch failed', [
                    'date' => $date,
                    'url' => $url,
                    'message' => $e->getMessage(),
                ]);
            }
        }
        return null;
    }

    protected function fetchBseData($dateObj)
    {
        $dateUnderscore = $dateObj->format('Ymd');
        $dateStr = $dateObj->format('dmy');
        $date = $dateObj->format('Y-m-d');

        $urls = [
            "https://www.bseindia.com/download/BhavCopy/Equity/BhavCopy_BSE_CM_0_0_0_{$dateUnderscore}_F_0000.CSV",
            "https://www.bseindia.com/download/BhavCopy/Equity/EQ{$dateStr}_CSV.ZIP",
        ];

        $warmupPages = [
            'https://www.bseindia.com/markets/MarketInfo/BhavCopy.aspx',
            'https://www.bseindia.com/markets/equity/EQReports/BhavCopy.aspx',
        ];

        $timeouts = [15, 30, 60];

        foreach ($timeouts as $attempt => $timeout) {
            if ($attempt > 0) {
                sleep(3);
            }

            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            try {
                Http::withOptions(['cookies' => $cookieJar])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                    ->timeout(10)
                    ->withoutVerifying()
                    ->get($warmupPages[$attempt % count($warmupPages)]);
            } catch (\Exception $e) {
                // Warmup failure is non-fatal
            }

            foreach ($urls as $url) {
                try {
                    $response = Http::withOptions(['cookies' => $cookieJar])
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Referer' => 'https://www.bseindia.com/markets/MarketInfo/BhavCopy.aspx',
                        ])
                        ->timeout($timeout)
                        ->withoutVerifying()
                        ->get($url);

                    if ($response->successful() && strlen($response->body()) > 500) {
                        Storage::put("equities/bhavcopies/{$date}/BSE_" . basename($url), $response->body());
                        return $this->parseFileContent($response->body(), 'BSE', $url);
                    }
                } catch (\Exception $e) {
                    Log::warning('equities:sync BSE fetch failed', [
                        'date' => $date,
                        'url' => $url,
                        'attempt' => $attempt + 1,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
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
            'isin'     => ['ISIN', 'FinInstrmId', 'ISIN_CODE'],
            'symbol'   => ['TckrSymb', 'SYMBOL', 'SC_NAME'],
            'name'     => ['FinInstrmNm', 'COMPANY_NAME', 'FULL_NAME'],
            'open'     => ['OpnPric', 'OPEN', 'OPEN_PRC'],
            'high'     => ['HghPric', 'HIGH', 'HIGH_PRC'],
            'low'      => ['LwPric', 'LOW', 'LOW_PRC'],
            'close'    => ['ClsPric', 'CLOSE', 'CLOSE_PRC', 'LAST_PRC'],
            'prev'     => ['PrvsClsgPric', 'PREVCLOSE', 'PREV_CLOSE'],
            'volume'   => ['TtlTradgVol', 'TOTTRDQTY', 'NO_SHARES', 'TRADE_QTY'],
            'turnover' => ['TtlTradgVal', 'TOTTRDVAL', 'NET_TURNOV'],
            'trades'   => ['TtlNbOfTradesExecuted', 'TOTALTRADES', 'NO_OF_TRDS'],
            'avg_price'=> ['WghtdAvgPric', 'AVG_PRICE', 'AVG_PRC'],
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
                    'open'      => (float)($mapped['open'] ?? 0),
                    'high'      => (float)($mapped['high'] ?? 0),
                    'low'       => (float)($mapped['low'] ?? 0),
                    'close'     => (float)($mapped['close'] ?? 0),
                    'prev_close'=> (float)($mapped['prev'] ?? 0),
                    'volume'    => (int)($mapped['volume'] ?? 0),
                    'turnover'  => (float)($mapped['turnover'] ?? 0),
                    'trades'    => (int)($mapped['trades'] ?? 0),
                    'avg_price' => (float)($mapped['avg_price'] ?? 0),
                    'exchange'  => $exchange,
                ]);
            }
        }
        fclose($stream);
        return $records;
    }

    protected function getHistoricalDateWindows($dateObj)
    {
        $periods = ['1d' => 1, '3d' => 3, '7d' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '9m' => 270, '1y' => 365, '3y' => 1095];
        $windowMap = [];
        $allTradingDates = DB::table('equity_prices')
            ->where('traded_date', '<', $dateObj->format('Y-m-d'))
            ->orderBy('traded_date', 'desc')
            ->limit(1200)
            ->pluck('traded_date');

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
            'nse_open', 'nse_high', 'nse_low', 'nse_close', 'nse_prev_close', 'nse_volume',
            'nse_turnover', 'nse_trades', 'nse_avg_price',
            'bse_open', 'bse_high', 'bse_low', 'bse_close', 'bse_prev_close', 'bse_volume',
            'bse_turnover', 'bse_trades', 'bse_avg_price',
            'nse_gap_pct', 'nse_range_pct', 'nse_intraday_chg_pct', 'nse_avg_ticket_size',
            'bse_gap_pct', 'bse_range_pct', 'bse_intraday_chg_pct', 'bse_avg_ticket_size',
            'nse_chg_1d', 'nse_val_1d',
            'nse_chg_3d', 'nse_val_3d',
            'nse_chg_7d', 'nse_val_7d',
            'nse_chg_1m', 'nse_val_1m',
            'nse_chg_3m', 'nse_val_3m',
            'nse_chg_6m', 'nse_val_6m',
            'nse_chg_9m', 'nse_val_9m',
            'nse_chg_1y', 'nse_val_1y',
            'nse_chg_3y', 'nse_val_3y',
            'bse_chg_1d', 'bse_val_1d',
            'bse_chg_3d', 'bse_val_3d',
            'bse_chg_7d', 'bse_val_7d',
            'bse_chg_1m', 'bse_val_1m',
            'bse_chg_3m', 'bse_val_3m',
            'bse_chg_6m', 'bse_val_6m',
            'bse_chg_9m', 'bse_val_9m',
            'bse_chg_1y', 'bse_val_1y',
            'bse_chg_3y', 'bse_val_3y',
            'spread', 'updated_at',
        ];
    }

    protected function setupSignals(): void
    {
        if (!extension_loaded('pcntl')) return;
        pcntl_async_signals(true);
        $handler = function (int $sig) {
            $this->shouldStop = true;
            $label = match ($sig) { SIGTERM => 'SIGTERM', SIGINT => 'SIGINT', default => "SIG{$sig}" };
            $this->warn("\n{$label} received — stopping after current step.");
            Log::warning('[equities:sync] signal received', ['signal' => $label]);
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    protected function detectPython()
    {
        if (!function_exists('exec')) return null;
        foreach (['python3', 'python', 'py'] as $candidate) {
            $out = [];
            $ret = 0;
            exec(escapeshellarg($candidate) . ' --version 2>&1', $out, $ret);
            if ($ret === 0) return $candidate;
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
