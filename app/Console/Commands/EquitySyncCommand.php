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
            if (!class_exists('ZipArchive')) {
                $this->error("PHP ZipArchive class not found. Please enable the zip extension.");
                return [];
            }
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
            'volume'   => ['TtlTradgVol', 'TOTTRDQTY', 'NO_SHARES', 'TOT_TR_QTY', 'TRADE_QTY'],
            'turnover' => ['TtlTradgVal', 'TOTTRDVAL', 'NET_TURNOV'],
            'trades'   => ['TtlNbOfTradesExecuted', 'TOTALTRADES', 'NO_OF_TRDS'],
            'avg_price' => ['WghtdAvgPric', 'AVG_PRICE', 'AVG_PRC']
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
                'turnover' => (float)($mapped['turnover'] ?? 0),
                'trades' => (int)($mapped['trades'] ?? 0),
                'avg_price' => (float)($mapped['avg_price'] ?? 0),
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
        $equitiesToUpsert = collect($data)->groupBy('isin')->map(function ($group, $isin) use ($existingEquities, $now) {
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
                'industry' => $existing ? $existing->industry : null,
                'market_cap' => $existing ? $existing->market_cap : null,
                'market_cap_category' => $existing ? $existing->market_cap_category : null,
                'face_value' => $existing ? $existing->face_value : null,
                'listing_date' => $existing ? $existing->listing_date : null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->toArray();

        $chunks = array_chunk($equitiesToUpsert, 50);
        foreach ($chunks as $chunk) {
            Equity::upsert($chunk, ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'face_value', 'listing_date', 'is_active', 'updated_at']);
        }

        // Step 2: Map ISIN to ID (Chunked fetch to avoid SQL variable limit)
        $isinToId = collect();
        foreach ($isins->chunk(500) as $isinChunk) {
            $isinToId = $isinToId->merge(Equity::whereIn('isin', $isinChunk)->pluck('id', 'isin'));
        }

        // Step 3: Identify historical dates for performance metrics
        $previousDates = EquityPrice::where('traded_date', '<', $date)
            ->select('traded_date')
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->limit(1000)
            ->pluck('traded_date')
            ->map(fn($d) => $d instanceof \Carbon\Carbon ? $d->format('Y-m-d') : $d);

        $dateMap = [
            '1d' => $previousDates->get(0),
            '3d' => $previousDates->get(2),
            '7d' => $previousDates->get(6),
            '1m' => $previousDates->get(20),
            '3m' => $previousDates->get(62),
            '6m' => $previousDates->get(125),
            '9m' => $previousDates->get(188),
            '1y' => $previousDates->get(251),
            '3y' => $previousDates->get(755),
        ];

        $targetDates = collect($dateMap)->filter()->unique()->values()->toArray();

        $historicalData = collect();
        if (!empty($targetDates)) {
            $this->info("Fetching historical data for " . count($targetDates) . " target dates...");
            // Chunked fetch for historical data to avoid variable limits
            foreach ($isinToId->values()->chunk(500) as $idChunk) {
                $batch = EquityPrice::whereIn('traded_date', $targetDates)
                    ->whereIn('equity_id', $idChunk)
                    ->get()
                    ->groupBy('equity_id');

                foreach ($batch as $eqId => $items) {
                    if (!$historicalData->has($eqId)) {
                        $historicalData->put($eqId, $items);
                    } else {
                        $historicalData->put($eqId, $historicalData->get($eqId)->merge($items));
                    }
                }
            }
        }

        // Step 4: Fetch existing prices for these ISINs on this specific date to enable additive sync
        $this->info('Merging and calculating metrics...');
        $existingPrices = collect();
        foreach ($isins->chunk(500) as $isinChunk) {
            $existingPrices = $existingPrices->merge(EquityPrice::whereIn('isin', $isinChunk)->where('traded_date', $date)->get()->keyBy('isin'));
        }

        // Step 5: Consolidate, merge and calculate metrics in one pass
        $consolidatedPrices = collect($data)->groupBy('isin')->map(function ($group, $isin) use ($isinToId, $date, $now, $existingPrices, $dateMap, $historicalData) {
            $nse = $group->where('exchange', 'NSE')->first();
            $bse = $group->where('exchange', 'BSE')->first();
            $existing = $existingPrices->get($isin);

            $equity_id = $isinToId[$isin] ?? null;
            if (!$equity_id) return null;

            // Price Data
            $nse_open = $nse ? $nse['open'] : ($existing ? $existing->nse_open : 0);
            $nse_high = $nse ? $nse['high'] : ($existing ? $existing->nse_high : 0);
            $nse_low = $nse ? $nse['low'] : ($existing ? $existing->nse_low : 0);
            $nse_close = $nse ? $nse['close'] : ($existing ? $existing->nse_close : 0);
            $nse_last = $nse ? $nse['last'] : ($existing ? $existing->nse_last : 0);
            $nse_prev = $nse ? $nse['prev_close'] : ($existing ? $existing->nse_prev_close : 0);
            $nse_vol = $nse ? $nse['volume'] : ($existing ? $existing->nse_volume : 0);
            $nse_turnover = $nse ? $nse['turnover'] : ($existing ? $existing->nse_turnover : 0);
            $nse_trades = $nse ? $nse['trades'] : ($existing ? $existing->nse_trades : 0);
            $nse_avg_price = $nse ? $nse['avg_price'] : ($existing ? $existing->nse_avg_price : 0);

            $bse_open = $bse ? $bse['open'] : ($existing ? $existing->bse_open : 0);
            $bse_high = $bse ? $bse['high'] : ($existing ? $existing->bse_high : 0);
            $bse_low = $bse ? $bse['low'] : ($existing ? $existing->bse_low : 0);
            $bse_close = $bse ? $bse['close'] : ($existing ? $existing->bse_close : 0);
            $bse_last = $bse ? $bse['last'] : ($existing ? $existing->bse_last : 0);
            $bse_prev = $bse ? $bse['prev_close'] : ($existing ? $existing->bse_prev_close : 0);
            $bse_vol = $bse ? $bse['volume'] : ($existing ? $existing->bse_volume : 0);
            $bse_turnover = $bse ? $bse['turnover'] : ($existing ? $existing->bse_turnover : 0);
            $bse_trades = $bse ? $bse['trades'] : ($existing ? $existing->bse_trades : 0);
            $bse_avg_price = $bse ? $bse['avg_price'] : ($existing ? $existing->bse_avg_price : 0);

            $spread = ($nse_close && $bse_close) ? abs($nse_close - $bse_close) : 0;

            // Metrics Calculation
            $history = $historicalData->get($equity_id);
            $historyByDate = $history ? $history->keyBy(fn($item) => $item->traded_date instanceof \Carbon\Carbon ? $item->traded_date->format('Y-m-d') : $item->traded_date) : null;

            $record = [
                'equity_id' => $equity_id,
                'isin' => $isin,
                'traded_date' => $date,
                'nse_open' => $nse_open,
                'nse_high' => $nse_high,
                'nse_low' => $nse_low,
                'nse_close' => $nse_close,
                'nse_last' => $nse_last,
                'nse_prev_close' => $nse_prev,
                'nse_volume' => $nse_vol,
                'nse_turnover' => $nse_turnover,
                'nse_trades' => $nse_trades,
                'nse_avg_price' => $nse_avg_price,
                'bse_open' => $bse_open,
                'bse_high' => $bse_high,
                'bse_low' => $bse_low,
                'bse_close' => $bse_close,
                'bse_last' => $bse_last,
                'bse_prev_close' => $bse_prev,
                'bse_volume' => $bse_vol,
                'bse_turnover' => $bse_turnover,
                'bse_trades' => $bse_trades,
                'bse_avg_price' => $bse_avg_price,
                'nse_chg_1d' => null,
                'nse_chg_3d' => null,
                'nse_chg_7d' => null,
                'nse_chg_1m' => null,
                'nse_chg_3m' => null,
                'nse_chg_6m' => null,
                'nse_chg_9m' => null,
                'nse_chg_1y' => null,
                'nse_chg_3y' => null,
                'bse_chg_1d' => null,
                'bse_chg_3d' => null,
                'bse_chg_7d' => null,
                'bse_chg_1m' => null,
                'bse_chg_3m' => null,
                'bse_chg_6m' => null,
                'bse_chg_9m' => null,
                'bse_chg_1y' => null,
                'bse_chg_3y' => null,
                'nse_gap_pct' => null,
                'bse_gap_pct' => null,
                'nse_intraday_chg_pct' => null,
                'bse_intraday_chg_pct' => null,
                'nse_range_pct' => null,
                'bse_range_pct' => null,
                'nse_avg_ticket_size' => null,
                'bse_avg_ticket_size' => null,
                'spread' => $spread,
                'created_at' => $existing ? $existing->created_at : $now,
                'updated_at' => $now,
            ];

            // NSE Analytical Calculations
            if ($nse) {
                if ($nse_prev > 0) {
                    $record['nse_gap_pct'] = (($nse_open - $nse_prev) / $nse_prev) * 100;
                    $record['nse_range_pct'] = (($nse_high - $nse_low) / $nse_prev) * 100;
                }
                if ($nse_open > 0) {
                    $record['nse_intraday_chg_pct'] = (($nse_close - $nse_open) / $nse_open) * 100;
                }
                if ($nse_trades > 0) {
                    $record['nse_avg_ticket_size'] = $nse_turnover / $nse_trades;
                }
            }

            // BSE Analytical Calculations
            if ($bse) {
                if ($bse_prev > 0) {
                    $record['bse_gap_pct'] = (($bse_open - $bse_prev) / $bse_prev) * 100;
                    $record['bse_range_pct'] = (($bse_high - $bse_low) / $bse_prev) * 100;
                }
                if ($bse_open > 0) {
                    $record['bse_intraday_chg_pct'] = (($bse_close - $bse_open) / $bse_open) * 100;
                }
                if ($bse_trades > 0) {
                    $record['bse_avg_ticket_size'] = $bse_turnover / $bse_trades;
                }
            }

            if ($historyByDate) {
                foreach (['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'] as $period) {
                    $prevDate = $dateMap[$period] ?? null;
                    $prev = $prevDate ? $historyByDate->get($prevDate) : null;
                    if ($prev) {
                        if ($nse_close && (float)$prev->nse_close > 0) $record["nse_chg_{$period}"] = (($nse_close - $prev->nse_close) / $prev->nse_close) * 100;
                        if ($bse_close && (float)$prev->bse_close > 0) $record["bse_chg_{$period}"] = (($bse_close - $prev->bse_close) / $prev->bse_close) * 100;
                    }
                }
            }

            return $record;
        })->filter()->values()->toArray();

        // Step 6: Single Bulk Upsert (Small chunks for SQLite variable limits)
        $this->info("Upserting " . count($consolidatedPrices) . " records with performance metrics...");
        $chunks = array_chunk($consolidatedPrices, 20);
        foreach ($chunks as $chunk) {
            EquityPrice::upsert($chunk, ['isin', 'traded_date'], [
                'nse_open',
                'nse_high',
                'nse_low',
                'nse_close',
                'nse_last',
                'nse_prev_close',
                'nse_volume',
                'nse_turnover',
                'nse_trades',
                'nse_avg_price',
                'bse_open',
                'bse_high',
                'bse_low',
                'bse_close',
                'bse_last',
                'bse_prev_close',
                'bse_volume',
                'bse_turnover',
                'bse_trades',
                'bse_avg_price',
                'nse_chg_1d',
                'nse_chg_3d',
                'nse_chg_7d',
                'nse_chg_1m',
                'nse_chg_3m',
                'nse_chg_6m',
                'nse_chg_9m',
                'nse_chg_1y',
                'nse_chg_3y',
                'bse_chg_1d',
                'bse_chg_3d',
                'bse_chg_7d',
                'bse_chg_1m',
                'bse_chg_3m',
                'bse_chg_6m',
                'bse_chg_9m',
                'bse_chg_1y',
                'bse_chg_3y',
                'nse_gap_pct', 'bse_gap_pct', 'nse_intraday_chg_pct', 'bse_intraday_chg_pct', 'nse_range_pct', 'bse_range_pct', 'nse_avg_ticket_size', 'bse_avg_ticket_size',
                'spread',
                'updated_at'
            ]);
        }

        $this->info("Sync completed successfully for {$date}.");
        return Command::SUCCESS;
    }
}
