<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Support\Facades\Log;

class EquitySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equities:sync {date?} {exchange?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and sync daily Bhavcopy data from NSE and/or BSE';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startDate = $this->argument('date') ?: now()->format('Y-m-d');
        $exchange = $this->argument('exchange');

        $currentDateObj = \Carbon\Carbon::parse($startDate);
        $attempts = 0;
        $maxAttempts = 10;
        
        $scriptPath = base_path('app/Scripts/fetch_bhavcopy.py');
        $pythonPaths = ['python', 'python3', 'py', 'C:\Python312\python.exe', 'C:\Python311\python.exe'];
        $pythonPath = null;
        
        foreach ($pythonPaths as $path) {
            $testOutput = [];
            $testReturn = 0;
            exec("{$path} --version 2>&1", $testOutput, $testReturn);
            if ($testReturn === 0) {
                $pythonPath = $path;
                break;
            }
        }

        while ($attempts < $maxAttempts) {
            $date = $currentDateObj->format('Y-m-d');
            $this->info("Checking sync for {$date}...");

            // Check if we already have records for this date
            // If we have records, we stop the subDay loop as per user request
            if (EquityPrice::where('traded_date', $date)->exists()) {
                $this->info("Records already exist for {$date}. Job complete.");
                return Command::SUCCESS;
            }

            // Attempt to fetch data via Background Worker (Python)
            if ($pythonPath) {
                $output = [];
                $returnVar = 0;
                exec("{$pythonPath} \"{$scriptPath}\" {$date} " . ($exchange ?: "") . " 2>&1", $output, $returnVar);

                $jsonData = '';
                foreach ($output as $line) {
                    if (strpos(trim($line), '[') === 0) {
                        $jsonData = $line;
                        break;
                    }
                }

                if (!empty($jsonData)) {
                    $data = json_decode($jsonData, true);
                    if ($data !== null && count($data) > 0) {
                        $this->info("Successfully fetched data via background worker for {$date}.");
                        return $this->processData($data, $date);
                    }
                }
            }

            // Attempt to fetch data via Native PHP Fallback
            $phpData = $this->handlePhpFetch($date, $exchange);
            if (!empty($phpData)) {
                $this->info("Successfully fetched data via native fetcher for {$date}.");
                return $this->processData($phpData, $date);
            }

            // If we are here, it means no data was found on exchange and no records in DB
            $this->warn("No data available for {$date}. Trying previous day...");
            $currentDateObj->subDay();
            $attempts++;
        }

        $this->error("Failed to find any data after {$maxAttempts} attempts.");
        return Command::FAILURE;
    }

    /**
     * Internal helper for fetching data via PHP fallback for a specific date.
     */
    protected function handlePhpFetch($date, $exchange)
    {
        $data = [];
        $dateObj = \Carbon\Carbon::parse($date);
        
        if (!$exchange || strtolower($exchange) === 'nse') {
            $this->info("  Fetching NSE data via PHP for {$date}...");
            $nseData = $this->fetchNseData($dateObj);
            if ($nseData) $data = array_merge($data, $nseData);
        }

        if (!$exchange || strtolower($exchange) === 'bse') {
            $this->info("  Fetching BSE data via PHP for {$date}...");
            $bseData = $this->fetchBseData($dateObj);
            if ($bseData) $data = array_merge($data, $bseData);
        }

        return $data;
    }

    /**
     * Native PHP fallback for syncing equity data.
     */
    protected function handlePhpFallback($date, $exchange)
    {
        $data = [];
        $dateObj = \Carbon\Carbon::parse($date);
        
        // Process NSE
        if (!$exchange || strtolower($exchange) === 'nse') {
            $this->info("Fetching NSE data via PHP...");
            $nseData = $this->fetchNseData($dateObj);
            if ($nseData) {
                $this->info("Successfully parsed " . count($nseData) . " NSE records.");
                $data = array_merge($data, $nseData);
            } else {
                $this->warn("No NSE records found or parsed.");
            }
        }

        // Process BSE
        if (!$exchange || strtolower($exchange) === 'bse') {
            $this->info("Fetching BSE data via PHP...");
            $bseData = $this->fetchBseData($dateObj);
            if ($bseData) {
                $this->info("Successfully parsed " . count($bseData) . " BSE records.");
                $data = array_merge($data, $bseData);
            } else {
                $this->warn("No BSE records found or parsed.");
            }
        }

        if (empty($data)) {
            $this->error("Failed to fetch data from both exchanges via PHP fallback.");
            return Command::FAILURE;
        }

        $this->info("Total records consolidated: " . count($data));
        return $this->processData($data, $date);
    }

    protected function fetchNseData($dateObj)
    {
        $dateStr = strtoupper($dateObj->format('dMY')); // 10APR2026
        $dateUnderscore = $dateObj->format('Ymd');     // 20260413
        $month = strtoupper($dateObj->format('M'));
        $year = $dateObj->format('Y');
        $date = $dateObj->format('Y-m-d');
        $dateTrad = strtoupper($dateObj->format('dMY')); // 13APR2026
        
        $urls = [
            // UDiFF Pattern (New)
            "https://nsearchives.nseindia.com/content/cm/BhavCopy_NSE_CM_0_0_0_{$dateUnderscore}_F_0000.csv.zip",
            // Archive Pattern (Traditional)
            "https://archives.nseindia.com/content/historical/EQUITIES/{$year}/{$month}/cm{$dateTrad}bhav.csv.zip",
            "https://www.nseindia.com/content/historical/EQUITIES/{$year}/{$month}/cm{$dateTrad}bhav.csv.zip"
        ];
        
        foreach ($urls as $url) {
            try {
                $this->info("Trying NSE URL: $url");
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Referer' => 'https://www.nseindia.com/all-reports'
                ])
                ->timeout(30)
                ->withoutVerifying()
                ->get($url);

                if ($response->successful() && strlen($response->body()) > 500) {
                    $path = "equities/bhavcopies/{$date}/NSE_" . basename($url);
                    \Illuminate\Support\Facades\Storage::put($path, $response->body());
                    $this->info("Saved NSE data to $path");

                    return $this->parseFileContent($response->body(), 'NSE', $url);
                }
            } catch (\Exception $e) {
                $this->warn("NSE fetch attempt failed for $url: " . $e->getMessage());
            }
        }
        return null;
    }

    protected function fetchBseData($dateObj)
    {
        $dateStr = $dateObj->format('dmy');           // 100426
        $dateUnderscore = $dateObj->format('Ymd');     // 20260410
        $date = $dateObj->format('Y-m-d');
        
        $urls = [
            // UDiFF Pattern (New)
            "https://www.bseindia.com/download/BhavCopy/Equity/BhavCopy_BSE_CM_0_0_0_{$dateUnderscore}_F_0000.CSV",
            // Traditional Pattern
            "https://www.bseindia.com/download/BhavCopy/Equity/EQ{$dateStr}_CSV.ZIP"
        ];

        foreach ($urls as $url) {
            try {
                $this->info("Trying BSE URL: $url");
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.bseindia.com/markets/MarketInfo/BhavCopy.aspx'
                ])
                ->timeout(30)
                ->withoutVerifying()
                ->get($url);

                if ($response->successful() && strlen($response->body()) > 500) {
                    $path = "equities/bhavcopies/{$date}/BSE_" . basename($url);
                    \Illuminate\Support\Facades\Storage::put($path, $response->body());
                    $this->info("Saved BSE data to $path");

                    return $this->parseFileContent($response->body(), 'BSE', $url);
                }
            } catch (\Exception $e) {
                $this->warn("BSE fetch failed for $url: " . $e->getMessage());
            }
        }
        return null;
    }

    protected function parseFileContent($content, $exchange, $url)
    {
        $isZip = str_ends_with(strtolower($url), '.zip');
        
        if ($isZip) {
            $tempFile = tempnam(sys_get_temp_dir(), 'bhav');
            file_put_contents($tempFile, $content);
            $zip = new \ZipArchive();
            $records = [];
            if ($zip->open($tempFile) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (str_ends_with(strtolower($filename), '.csv')) {
                        $csvContent = $zip->getFromIndex($i);
                        $records = array_merge($records, $this->parseCsvString($csvContent, $exchange));
                    }
                }
                $zip->close();
            }
            unlink($tempFile);
            return $records;
        } else {
            return $this->parseCsvString($content, $exchange);
        }
    }

    protected function parseCsvString($csvString, $exchange)
    {
        $lines = explode("\n", str_replace("\r", "", $csvString));
        if (count($lines) < 2) return [];

        $headers = array_map('trim', str_getcsv($lines[0]));
        $records = [];

        // Define column mappings for different formats (Traditional & UDiFF)
        $map = [
            'isin'    => ['ISIN', 'FinInstrmId', 'ISIN_CODE'],
            'symbol'  => ['TckrSymb', 'SYMBOL', 'SC_NAME'],
            'name'    => ['FinInstrmNm', 'COMPANY_NAME', 'FULL_NAME'],
            'open'    => ['OpnPric', 'OPEN', 'OPEN_PRC'],
            'high'    => ['HghPric', 'HIGH', 'HIGH_PRC'],
            'low'     => ['LwPric', 'LOW', 'LOW_PRC'],
            'close'   => ['ClsPric', 'CLOSE', 'CLOSE_PRC', 'LAST_PRC', 'LAST'],
            'last'    => ['LastPric', 'LAST', 'LTP', 'LAST_PRC'],
            'prev'    => ['PrvsClsgPric', 'PREVCLOSE', 'PREV_CLOSE', 'PREV_CLSG_PRC'],
            'volume'  => ['TtlTradgVol', 'TOTTRDQTY', 'NO_SHARES', 'TOT_TR_QTY', 'TRADE_QTY']
        ];

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $row = str_getcsv($line);
            
            // NSE UDiFF files often have a trailing comma in the header but not the rows (or vice-versa)
            // We'll take the minimum count to allow array_combine to work robustly
            $count = min(count($headers), count($row));
            if ($count < 10) continue; // Basic sanity check

            $data = array_combine(array_slice($headers, 0, $count), array_slice($row, 0, $count));
            $mapped = [];

            foreach ($map as $key => $candidates) {
                foreach ($candidates as $cand) {
                    if (isset($data[$cand])) {
                        $mapped[$key] = trim($data[$cand]);
                        break;
                    }
                }
            }

            if (empty($mapped['isin'])) continue;

            $records[] = [
                'isin' => $mapped['isin'],
                'symbol' => $mapped['symbol'] ?? '',
                'name' => $mapped['name'] ?? '',
                'open' => (float)($mapped['open'] ?? 0),
                'high' => (float)($mapped['high'] ?? 0),
                'low' => (float)($mapped['low'] ?? 0),
                'close' => (float)($mapped['close'] ?? 0),
                'last' => (float)($mapped['last'] ?? 0),
                'prev_close' => (float)($mapped['prev'] ?? 0),
                'volume' => (int)($mapped['volume'] ?? 0),
                'exchange' => $exchange
            ];
        }
        return $records;
    }

    protected function processData($data, $date)
    {
        $this->info('Syncing unique equities...');
        
        $isins = collect($data)->pluck('isin')->unique();
        $existingEquities = Equity::whereIn('isin', $isins)->get()->keyBy('isin');

        $now = now();
        $equitiesToUpsert = collect($data)->groupBy('isin')->map(function($group, $isin) use ($existingEquities, $now) {
            $nse = $group->where('exchange', 'NSE')->first();
            $bse = $group->where('exchange', 'BSE')->first();
            $existing = $existingEquities->get($isin);
            
            // Priority for name: UDiFF Name > Existing Name > Ticker Symbol
            $name = ($nse && !empty($nse['name'])) ? $nse['name'] : (($bse && !empty($bse['name'])) ? $bse['name'] : ($existing ? $existing->company_name : ($nse ? $nse['symbol'] : ($bse ? $bse['symbol'] : ''))));

            return [
                'isin' => $isin,
                'company_name' => $name,
                'nse_symbol' => $nse ? $nse['symbol'] : ($existing ? $existing->nse_symbol : null),
                'bse_symbol' => $bse ? $bse['symbol'] : ($existing ? $existing->bse_symbol : null),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->toArray();
        
        $chunks = array_chunk($equitiesToUpsert, 500);
        foreach ($chunks as $chunk) {
            Equity::upsert($chunk, ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'is_active', 'updated_at']);
        }
        
        // Step 2: Map ISIN to ID
        $isinToId = Equity::whereIn('isin', $isins)->pluck('id', 'isin');

        // Step 3: Fetch existing prices for these ISINs on this specific date to enable additive sync
        $this->info('Merging with existing price data...');
        $existingPrices = EquityPrice::whereIn('isin', $isins)->where('traded_date', $date)->get()->keyBy('isin');

        // Step 4: Consolidate and merge
        $consolidatedPrices = collect($data)->groupBy('isin')->map(function($group, $isin) use ($isinToId, $date, $now, $existingPrices) {
            $nse = $group->where('exchange', 'NSE')->first();
            $bse = $group->where('exchange', 'BSE')->first();
            $existing = $existingPrices->get($isin);
            
            // NSE Data (Use new if exists, otherwise keep existing)
            $nse_open = $nse ? $nse['open'] : ($existing ? $existing->nse_open : 0);
            $nse_high = $nse ? $nse['high'] : ($existing ? $existing->nse_high : 0);
            $nse_low = $nse ? $nse['low'] : ($existing ? $existing->nse_low : 0);
            $nse_close = $nse ? $nse['close'] : ($existing ? $existing->nse_close : 0);
            $nse_last = $nse ? $nse['last'] : ($existing ? $existing->nse_last : 0);
            $nse_prev = $nse ? $nse['prev_close'] : ($existing ? $existing->nse_prev_close : 0);
            $nse_vol = $nse ? $nse['volume'] : ($existing ? $existing->nse_volume : 0);

            // BSE Data
            $bse_open = $bse ? $bse['open'] : ($existing ? $existing->bse_open : 0);
            $bse_high = $bse ? $bse['high'] : ($existing ? $existing->bse_high : 0);
            $bse_low = $bse ? $bse['low'] : ($existing ? $existing->bse_low : 0);
            $bse_close = $bse ? $bse['close'] : ($existing ? $existing->bse_close : 0);
            $bse_last = $bse ? $bse['last'] : ($existing ? $existing->bse_last : 0);
            $bse_prev = $bse ? $bse['prev_close'] : ($existing ? $existing->bse_prev_close : 0);
            $bse_vol = $bse ? $bse['volume'] : ($existing ? $existing->bse_volume : 0);

            $spread = ($nse_close && $bse_close) ? abs($nse_close - $bse_close) : 0;

            return [
                'equity_id' => $isinToId[$isin] ?? null,
                'isin' => $isin,
                'traded_date' => $date,
                'nse_open' => $nse_open,
                'nse_high' => $nse_high,
                'nse_low' => $nse_low,
                'nse_close' => $nse_close,
                'nse_last' => $nse_last,
                'nse_prev_close' => $nse_prev,
                'nse_volume' => $nse_vol,
                'bse_open' => $bse_open,
                'bse_high' => $bse_high,
                'bse_low' => $bse_low,
                'bse_close' => $bse_close,
                'bse_last' => $bse_last,
                'bse_prev_close' => $bse_prev,
                'bse_volume' => $bse_vol,
                'spread' => $spread,
                'created_at' => $existing ? $existing->created_at : $now,
                'updated_at' => $now,
            ];
        })->filter(fn($p) => $p['equity_id'] !== null)->values()->toArray();

        // Process in chunks to avoid memory issues and SQL limits
        $chunks = array_chunk($consolidatedPrices, 1000);
        foreach ($chunks as $chunk) {
            EquityPrice::upsert($chunk, ['isin', 'traded_date'], [
                'nse_open', 'nse_high', 'nse_low', 'nse_close', 'nse_last', 'nse_prev_close', 'nse_volume',
                'bse_open', 'bse_high', 'bse_low', 'bse_close', 'bse_last', 'bse_prev_close', 'bse_volume',
                'nse_chg_1d', 'nse_chg_3d', 'nse_chg_7d', 'nse_chg_1m',
                'bse_chg_1d', 'bse_chg_3d', 'bse_chg_7d', 'bse_chg_1m',
                'spread', 'updated_at'
            ]);
        }

        $this->info("Calculating performance metrics for {$date}...");
        $this->calculateMetrics($date);

        $this->info("Sync completed successfully for {$date}.");
        return Command::SUCCESS;
    }

    /**
     * Calculate 1d, 3d, 7d, 1m performance percentage changes for all stocks on a given date.
     */
    protected function calculateMetrics($date)
    {
        $currentPrices = EquityPrice::where('traded_date', $date)->get();
        if ($currentPrices->isEmpty()) return;

        // For each stock, fetch previous records to compute changes
        foreach ($currentPrices as $p) {
            $history = EquityPrice::where('equity_id', $p->equity_id)
                ->where('traded_date', '<', $date)
                ->orderBy('traded_date', 'desc')
                ->limit(25) // Up to 1 month of trading days
                ->get();

            if ($history->isEmpty()) continue;

            // 1 Day Change
            $prev1 = $history->get(0);
            if ($prev1) {
                if ($p->nse_close && $prev1->nse_close) $p->nse_chg_1d = (($p->nse_close - $prev1->nse_close) / $prev1->nse_close) * 100;
                if ($p->bse_close && $prev1->bse_close) $p->bse_chg_1d = (($p->bse_close - $prev1->bse_close) / $prev1->bse_close) * 100;
            }

            // 3 Day Change
            $prev3 = $history->get(2);
            if ($prev3) {
                if ($p->nse_close && $prev3->nse_close) $p->nse_chg_3d = (($p->nse_close - $prev3->nse_close) / $prev3->nse_close) * 100;
                if ($p->bse_close && $prev3->bse_close) $p->bse_chg_3d = (($p->bse_close - $prev3->bse_close) / $prev3->bse_close) * 100;
            }

            // 7 Day Change
            $prev7 = $history->get(6);
            if ($prev7) {
                if ($p->nse_close && $prev7->nse_close) $p->nse_chg_7d = (($p->nse_close - $prev7->nse_close) / $prev7->nse_close) * 100;
                if ($p->bse_close && $prev7->bse_close) $p->bse_chg_7d = (($p->bse_close - $prev7->bse_close) / $prev7->bse_close) * 100;
            }

            // 1 Month Change (approx 21 trading days)
            $prev1M = $history->get(20);
            if ($prev1M) {
                if ($p->nse_close && $prev1M->nse_close) $p->nse_chg_1m = (($p->nse_close - $prev1M->nse_close) / $prev1M->nse_close) * 100;
                if ($p->bse_close && $prev1M->bse_close) $p->bse_chg_1m = (($p->bse_close - $prev1M->bse_close) / $prev1M->bse_close) * 100;
            }

            $p->save();
        }
    }
}
