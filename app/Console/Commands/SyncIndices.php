<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Index;
use App\Models\IndexPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncIndices extends Command
{
    protected $signature = 'indices:sync
                            {date?  : Trading date YYYY-MM-DD (defaults to today)}
                            {exchange? : NSE or BSE (default: both)}
                            {--analytics-only : Skip fetch, just recalculate analytics for existing data}';

    protected $description = 'Sync daily stock market indices from NSE and BSE';

    public function handle(): int
    {
        // Reconnect upfront — this command may be invoked after a long idle gap
        // (scheduler, history loop) and the connection may already be dead.
        try { DB::reconnect(); } catch (\Exception $e) {}

        $dateArg        = $this->argument('date');
        $exchange       = strtoupper($this->argument('exchange') ?? '');
        $isExplicitDate = !is_null($dateArg);          // history command passes an explicit date
        $currentDateObj = Carbon::parse($dateArg ?: now());

        // ── Analytics-only mode ──────────────────────────────────────────────
        if ($this->option('analytics-only')) {
            $dateStr = $currentDateObj->format('Y-m-d');
            if (!IndexPrice::where('traded_date', $dateStr)->exists()) {
                $this->warn("No data found for {$dateStr}, skipping analytics.");
                return Command::SUCCESS;
            }
            $this->info("Recalculating analytics for {$currentDateObj->format('d/m/Y')}...");
            $this->calculateAnalytics($currentDateObj);
            return Command::SUCCESS;
        }

        // ── Fetch loop ───────────────────────────────────────────────────────
        // When an explicit date is passed (history command), try only that date.
        // When run as daily cron (no date), seek backward up to 10 days to find
        // the most recent trading day that has data.
        $maxAttempts = $isExplicitDate ? 1 : 10;
        $attempts    = 0;

        while ($attempts < $maxAttempts) {
            $dateStr = $currentDateObj->format('Y-m-d');
            $this->info("Syncing indices for: {$currentDateObj->format('d/m/Y')}");

            // ── Check if already fully synced ────────────────────────────────
            $query = IndexPrice::where('traded_date', $dateStr);
            if ($exchange) {
                $query->whereHas('index', fn($q) => $q->where('exchange', $exchange));
            }
            if ($query->exists()) {
                $this->info("  Data already exists for {$dateStr}. Nothing to do.");
                return Command::SUCCESS;
            }

            // ── Fetch from each exchange ─────────────────────────────────────
            $syncedNse = 0;
            $syncedBse = 0;

            try {
                if (!$exchange || $exchange === 'NSE') {
                    $syncedNse = $this->syncNiftyIndices($currentDateObj);
                    $this->info("  NSE: {$syncedNse} indices fetched.");
                }

                if (!$exchange || $exchange === 'BSE') {
                    $syncedBse = $this->syncBseIndices($currentDateObj);
                    $this->info("  BSE: {$syncedBse} indices fetched.");
                }
            } catch (\Exception $e) {
                $this->error("  Error during sync for {$dateStr}: " . $e->getMessage());
            }

            // ── Reconnect after potentially long HTTP work ───────────────────
            try { DB::reconnect(); } catch (\Exception $e) {}

            // ── Verify and calculate analytics ───────────────────────────────
            $saved = IndexPrice::where('traded_date', $dateStr)->count();
            $this->info("  Total records in DB for {$dateStr}: {$saved}");

            if ($syncedNse > 0 || $syncedBse > 0) {
                $this->calculateAnalytics($currentDateObj);
                $this->info("  Sync complete for {$dateStr}. Analytics updated.");
                return Command::SUCCESS;
            }

            // ── No data found ────────────────────────────────────────────────
            if ($isExplicitDate) {
                // Market holiday or data not yet published — not an error
                $this->warn("  No data available for {$dateStr} (holiday or not yet published).");
                return Command::SUCCESS;
            }

            $this->warn("  No data for {$dateStr}. Trying previous trading day...");
            $currentDateObj->subDay();
            $attempts++;
        }

        $this->error("Failed to find data after {$maxAttempts} attempts.");
        return Command::FAILURE;
    }

    // ── Analytics ────────────────────────────────────────────────────────────

    private function calculateAnalytics(Carbon $date): void
    {
        $this->info("  Calculating analytical metrics...");

        $prices = IndexPrice::where('traded_date', $date->format('Y-m-d'))->get();

        if ($prices->isEmpty()) {
            $this->warn("  No prices found for {$date->format('d/m/Y')} to calculate analytics.");
            return;
        }

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

        $oldestTarget  = $date->copy()->subYears(3)->subDays(15)->format('Y-m-d');
        $tradingDates  = IndexPrice::where('traded_date', '<', $date->format('Y-m-d'))
            ->where('traded_date', '>=', $oldestTarget)
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->pluck('traded_date')
            ->map(fn($d) => Carbon::parse($d instanceof Carbon ? $d->format('Y-m-d') : (string)$d));

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

        $historicalData = IndexPrice::whereIn('traded_date', $allTargetDates)
            ->get()
            ->groupBy('index_code');

        foreach ($prices as $price) {
            $code          = $price->index_code;
            $history       = $historicalData->get($code);
            $historyByDate = $history
                ? $history->keyBy(fn($item) => $item->traded_date instanceof Carbon
                    ? $item->traded_date->format('Y-m-d')
                    : (string)$item->traded_date)
                : null;

            // Auto-fill prev_close from 1d window if missing
            if (!$price->prev_close && $historyByDate) {
                foreach ($dateWindowMap['1d'] as $pd) {
                    $last = $historyByDate->get($pd);
                    if ($last && $last->close > 0) {
                        $price->prev_close = $last->close;
                        break;
                    }
                }
            }

            // Core OHLC analytics
            if ($price->prev_close && $price->prev_close > 0) {
                if ($price->open) {
                    $price->gap_pct = (($price->open - $price->prev_close) / $price->prev_close) * 100;
                }
                $price->range_pct = (($price->high - $price->low) / $price->prev_close) * 100;
            }
            if ($price->open && $price->open > 0) {
                $price->intraday_chg_pct = (($price->close - $price->open) / $price->open) * 100;
            }

            // Historical period returns
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

        $this->info("  Analytics done for " . count($prices) . " indices.");
    }

    // ── NSE Sync ─────────────────────────────────────────────────────────────

    private function syncNiftyIndices(Carbon $date): int
    {
        $url = "https://www.niftyindices.com/Daily_Snapshot/ind_close_all_" . $date->format('dmY') . ".csv";
        $this->info("  [NSE] Fetching: {$url}");

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer'    => 'https://www.niftyindices.com/reports/daily-reports',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(30)->get($url);

            if ($response->failed()) {
                $this->warn("  [NSE] HTTP {$response->status()} — no data for this date.");
                return 0;
            }

            $csvData = $response->body();

            if (
                str_contains(strtolower($response->header('Content-Type')), 'text/html') ||
                str_starts_with(trim($csvData), '<!DOCTYPE') ||
                str_starts_with(trim($csvData), '<html')
            ) {
                $this->warn("  [NSE] Received HTML — market closed or archive unavailable.");
                return 0;
            }

            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);
            $lines   = explode("\n", str_replace("\r", "", trim($csvData)));
            if (empty($lines)) return 0;

            $header = str_getcsv(array_shift($lines));
            $map    = array_flip(array_map('trim', $header));

            if (!isset($map['Index Name'])) {
                $this->warn("  [NSE] Column 'Index Name' not found. Headers: " . implode(', ', array_keys($map)));
                return 0;
            }

            $indicesData = [];
            $pricesData  = [];
            $now         = now();

            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < count($header)) continue;

                $rawName = trim($row[$map['Index Name']]);
                if (empty($rawName)) continue;

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
                    'open'           => $this->parseFloat($row[$map['Open Index Value']    ?? $map['Open']  ?? 0]),
                    'high'           => $this->parseFloat($row[$map['High Index Value']    ?? $map['High']  ?? 0]),
                    'low'            => $this->parseFloat($row[$map['Low Index Value']     ?? $map['Low']   ?? 0]),
                    'close'          => $this->parseFloat($row[$map['Closing Index Value'] ?? $map['Close'] ?? 0]),
                    'prev_close'     => null,
                    'change_percent' => $this->parseFloat($row[$map['Change(%)'] ?? $map['Percent Change'] ?? 0]),
                    'volume'         => $this->parseFloat($row[$map['Volume']    ?? 0]),
                    'turnover'       => $this->parseFloat($row[$map['Turnover (Rs. Cr.)'] ?? $map['Turnover'] ?? 0]),
                    'pe_ratio'       => $this->parseFloat($row[$map['P/E']       ?? 0]),
                    'pb_ratio'       => $this->parseFloat($row[$map['P/B']       ?? 0]),
                    'div_yield'      => $this->parseFloat($row[$map['Div Yield'] ?? 0]),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (empty($pricesData)) {
                $this->warn("  [NSE] No valid rows in CSV.");
                return 0;
            }

            Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
            IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
                'open', 'high', 'low', 'close', 'prev_close', 'change_percent',
                'volume', 'turnover', 'pe_ratio', 'pb_ratio', 'div_yield', 'updated_at',
            ]);

            return count($pricesData);

        } catch (\Exception $e) {
            $this->warn("  [NSE] Exception: " . $e->getMessage());
            return 0;
        }
    }

    // ── BSE Sync ─────────────────────────────────────────────────────────────

    private function syncBseIndices(Carbon $date): int
    {
        $urls = [
            "https://www.bseindia.com/Downloads/AllIndices/AllIndices_"           . $date->format('dmY')  . ".csv",
            "https://www.bseindia.com/download/BhavCopy/Index/IndexBhavCopy_"     . $date->format('dmy')  . ".zip",
            "https://www.bseindia.com/bsedata/Index_Bhavcopy/INDEXSummary_"       . $date->format('dmY')  . ".csv",
            "https://www.bseindia.com/bsedata/Index_Bhavcopy/INDEXSummary_"       . $date->format('dmy')  . ".csv",
            "https://www.bseindia.com/Downloads/MarketInfo/Indices_"               . $date->format('dmy')  . ".zip",
            "https://www.bseindia.com/download/BhavCopy/Index/indexbhavcopy"       . $date->format('Ymd')  . ".csv",
            "https://www.bseindia.com/download/BhavCopy/Index/Indexbhavcopy"       . $date->format('Ymd')  . ".csv",
            "https://www.bseindia.com/download/BhavCopy/Index/indexbhavcopy_"      . $date->format('Ymd')  . ".csv",
            "https://www.bseindia.com/download/allindices/allindices_"             . $date->format('dmY')  . ".csv",
        ];

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer'    => 'https://www.bseindia.com/markets/MarketInfo/DispMarkInfoStat.aspx',
            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];

        $warmupPages = [
            'https://www.bseindia.com/markets/MarketInfo/DispMarkInfoStat.aspx',
            'https://www.bseindia.com/markets/equity/EQReports/BhavCopy.aspx',
        ];

        $csvData    = null;
        $successUrl = null;

        // 3-attempt retry with escalating timeouts.
        // connect_timeout (5s) ensures blocked connections fail fast without
        // waiting for the full request timeout — critical on production servers
        // where BSE may block datacenter IPs at the TCP level.
        $timeouts = [15, 30, 60];

        foreach ($timeouts as $attempt => $timeout) {
            if ($attempt > 0) {
                $this->warn("  [BSE] Retry attempt {$attempt} (timeout: {$timeout}s)...");
                sleep(3);
            }

            // Fresh cookie jar and warmup on each attempt
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            try {
                Http::withOptions([
                    'cookies'         => $cookieJar,
                    'connect_timeout' => 5,
                ])->withHeaders($headers)->timeout(10)->get($warmupPages[$attempt % count($warmupPages)]);
            } catch (\Exception $e) {
                // Warmup failure is non-fatal — carry on
            }

            foreach ($urls as $url) {
                $this->info("  [BSE] Trying: {$url}");
                try {
                    $response = Http::withOptions([
                        'cookies'         => $cookieJar,
                        'connect_timeout' => 5,   // fail fast if IP is blocked
                    ])->withHeaders($headers)->timeout($timeout)->get($url);

                    if ($response->successful()) {
                        $body = $response->body();
                        $isHtml = str_contains(strtolower($response->header('Content-Type')), 'text/html')
                               || str_starts_with(trim($body), '<!DOCTYPE')
                               || str_starts_with(trim($body), '<html');

                        if (!$isHtml) {
                            $csvData    = $body;
                            $successUrl = $url;
                            $this->info("  [BSE] Got data from: {$url}");
                            break 2;
                        }
                        $this->warn("  [BSE] HTML response (not data) from: {$url}");
                    } else {
                        $this->warn("  [BSE] HTTP {$response->status()} from: {$url}");
                    }
                } catch (\Exception $e) {
                    $this->warn("  [BSE] {$url}: " . $e->getMessage());
                }
            }
        }

        if (!$csvData) {
            $this->warn("  [BSE] All direct URLs failed. Falling back to Yahoo Finance...");
            return $this->syncBseViaYahoo($date);
        }

        return $this->parseBseCsv($csvData, $successUrl, $date);
    }

    private function parseBseCsv(string $csvData, string $sourceUrl, Carbon $date): int
    {
        try {
            // Handle ZIP archives
            if (str_ends_with(strtolower($sourceUrl), '.zip')) {
                if (!class_exists('ZipArchive')) {
                    $this->error("  [BSE] PHP ZipArchive extension not available.");
                    return 0;
                }
                $tempFile = tempnam(sys_get_temp_dir(), 'bse_idx');
                file_put_contents($tempFile, $csvData);
                $zip     = new \ZipArchive();
                $csvData = '';
                if ($zip->open($tempFile) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        if (str_ends_with(strtolower($zip->getNameIndex($i)), '.csv')) {
                            $csvData = $zip->getFromIndex($i);
                            break;
                        }
                    }
                    $zip->close();
                }
                unlink($tempFile);
                if (empty($csvData)) {
                    $this->warn("  [BSE] No CSV found inside ZIP.");
                    return 0;
                }
            }

            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);
            $lines   = explode("\n", str_replace("\r", "", trim($csvData)));
            if (empty($lines)) return 0;

            $header = str_getcsv(array_shift($lines));
            $map    = array_flip(array_map('trim', $header));

            // BSE has changed column names across formats — cover all known variations
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
                $this->warn("  [BSE] Index Name column not found. Headers: " . implode(', ', $header));
                return 0;
            }

            $indicesData = [];
            $pricesData  = [];
            $now         = now();

            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < count($header)) continue;

                $rawName = trim($row[$resolvedMap['name']]);
                if (empty($rawName)) continue;

                $code = 'BSE_' . str_replace([' ', '&', '(', ')'], '_', strtoupper($rawName));

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
                    'open'           => $this->parseFloat($row[$resolvedMap['open']     ?? -1] ?? 0),
                    'high'           => $this->parseFloat($row[$resolvedMap['high']     ?? -1] ?? 0),
                    'low'            => $this->parseFloat($row[$resolvedMap['low']      ?? -1] ?? 0),
                    'close'          => $this->parseFloat($row[$resolvedMap['close']    ?? -1] ?? 0),
                    'prev_close'     => $this->parseFloat($row[$resolvedMap['prev']     ?? -1] ?? 0),
                    'change_percent' => $this->parseFloat($row[$resolvedMap['change']   ?? -1] ?? 0),
                    'volume'         => $this->parseFloat($row[$resolvedMap['vol']      ?? -1] ?? 0),
                    'turnover'       => $this->parseFloat($row[$resolvedMap['turnover'] ?? -1] ?? 0),
                    'pe_ratio'       => $this->parseFloat($row[$resolvedMap['pe']       ?? -1] ?? 0),
                    'pb_ratio'       => $this->parseFloat($row[$resolvedMap['pb']       ?? -1] ?? 0),
                    'div_yield'      => $this->parseFloat($row[$resolvedMap['yield']    ?? -1] ?? 0),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (empty($pricesData)) {
                $this->warn("  [BSE] No valid rows parsed from CSV.");
                return 0;
            }

            Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
            foreach (array_chunk($pricesData, 100) as $chunk) {
                IndexPrice::upsert($chunk, ['index_code', 'traded_date'], [
                    'open', 'high', 'low', 'close', 'prev_close', 'change_percent',
                    'volume', 'turnover', 'pe_ratio', 'pb_ratio', 'div_yield', 'updated_at',
                ]);
            }

            return count($pricesData);

        } catch (\Exception $e) {
            $this->warn("  [BSE] CSV parse failed: " . $e->getMessage());
            return 0;
        }
    }

    // ── BSE Yahoo Finance fallback ────────────────────────────────────────────

    private function syncBseViaYahoo(Carbon $date): int
    {
        $tickers = [
            '^BSESN'          => ['name' => 'S&P BSE SENSEX',           'code' => 'BSE_SENSEX',   'cat' => 'Broad-based'],
            'BSE-100.BO'      => ['name' => 'S&P BSE 100',               'code' => 'BSE_100',      'cat' => 'Broad-based'],
            'BSE-200.BO'      => ['name' => 'S&P BSE 200',               'code' => 'BSE_200',      'cat' => 'Broad-based'],
            'BSE-500.BO'      => ['name' => 'S&P BSE 500',               'code' => 'BSE_500',      'cat' => 'Broad-based'],
            'BSE-MidCap.BO'   => ['name' => 'S&P BSE MidCap',            'code' => 'BSE_MIDCAP',   'cat' => 'Broad-based'],
            'BSE-SmlCap.BO'   => ['name' => 'S&P BSE SmallCap',          'code' => 'BSE_SMALLCAP', 'cat' => 'Broad-based'],
            'BSE-LargeCap.BO' => ['name' => 'S&P BSE LargeCap',          'code' => 'BSE_LARGECAP', 'cat' => 'Broad-based'],
            'BSEALLCAP.BO'    => ['name' => 'S&P BSE AllCap',            'code' => 'BSE_ALLCAP',   'cat' => 'Broad-based'],
            'BSE-BANK.BO'     => ['name' => 'S&P BSE BANKEX',            'code' => 'BSE_BANKEX',   'cat' => 'Sectoral'],
            'BSE-IT.BO'       => ['name' => 'S&P BSE IT',                'code' => 'BSE_IT',       'cat' => 'Sectoral'],
            'BSE-AUTO.BO'     => ['name' => 'S&P BSE AUTO',              'code' => 'BSE_AUTO',     'cat' => 'Sectoral'],
            'BSE-FMCG.BO'     => ['name' => 'S&P BSE FMCG',              'code' => 'BSE_FMCG',     'cat' => 'Sectoral'],
            'BSE-HC.BO'       => ['name' => 'S&P BSE Healthcare',        'code' => 'BSE_HC',       'cat' => 'Sectoral'],
            'BSE-METAL.BO'    => ['name' => 'S&P BSE Metal',             'code' => 'BSE_METAL',    'cat' => 'Sectoral'],
            'BSE-OIL.BO'      => ['name' => 'S&P BSE Oil & Gas',         'code' => 'BSE_OIL',      'cat' => 'Sectoral'],
            'BSE-PWR.BO'      => ['name' => 'S&P BSE Power',             'code' => 'BSE_PWR',      'cat' => 'Sectoral'],
            'BSE-TECK.BO'     => ['name' => 'S&P BSE Teck',              'code' => 'BSE_TECK',     'cat' => 'Sectoral'],
            'BSE-CD.BO'       => ['name' => 'S&P BSE Consumer Durables', 'code' => 'BSE_CD',       'cat' => 'Sectoral'],
            'BSE-CG.BO'       => ['name' => 'S&P BSE Capital Goods',     'code' => 'BSE_CG',       'cat' => 'Sectoral'],
            'BSE-Realty.BO'   => ['name' => 'S&P BSE Realty',            'code' => 'BSE_REALTY',   'cat' => 'Sectoral'],
        ];

        $pricesData  = [];
        $indicesData = [];
        $now         = now();
        $dateStr     = $date->format('Y-m-d');

        $tsStart = $date->copy()->startOfDay()->timestamp;
        $tsEnd   = $date->copy()->endOfDay()->timestamp;

        foreach ($tickers as $ticker => $meta) {
            try {
                $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}"
                     . "?period1={$tsStart}&period2={$tsEnd}&interval=1d";

                $this->info("  [Yahoo] {$meta['name']}...");

                $response = Http::withOptions(['connect_timeout' => 5])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->timeout(10)
                    ->get($url);

                if (!$response->successful()) {
                    $this->warn("  [Yahoo] HTTP {$response->status()} for {$ticker}");
                    continue;
                }

                $json   = $response->json();
                $result = $json['chart']['result'][0] ?? null;
                if (!$result || empty($result['timestamp'])) continue;

                $quote = $result['indicators']['quote'][0] ?? null;
                if (!$quote || empty($quote['close'][0])) continue;

                $meta2     = $result['meta'] ?? [];
                $prevClose = isset($meta2['chartPreviousClose']) ? (float)$meta2['chartPreviousClose'] : null;
                $closeVal  = (float)($quote['close'][0] ?? 0);
                $changePct = ($prevClose && $prevClose > 0)
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
                    'open'           => (float)($quote['open'][0]   ?? 0),
                    'high'           => (float)($quote['high'][0]   ?? 0),
                    'low'            => (float)($quote['low'][0]    ?? 0),
                    'close'          => $closeVal,
                    'prev_close'     => $prevClose,
                    'change_percent' => $changePct,
                    'volume'         => (float)($quote['volume'][0] ?? 0),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

            } catch (\Exception $e) {
                $this->warn("  [Yahoo] {$ticker}: " . $e->getMessage());
            }
        }

        if (empty($indicesData)) {
            $this->warn("  [Yahoo] No BSE data retrieved.");
            return 0;
        }

        Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
        IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
            'open', 'high', 'low', 'close', 'prev_close', 'change_percent', 'volume', 'updated_at',
        ]);

        $this->info("  [Yahoo] Saved " . count($pricesData) . " BSE indices.");
        return count($pricesData);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function guessCategory(string $name): string
    {
        $name = strtolower($name);
        if (str_contains($name, 'sectoral') || str_contains($name, 'bank') ||
            str_contains($name, ' it')       || str_contains($name, 'auto')) {
            return 'Sectoral';
        }
        if (str_contains($name, 'nifty 50') || str_contains($name, 'nifty 100') ||
            str_contains($name, 'next 50')) {
            return 'Broad-based';
        }
        return 'Thematic';
    }

    private function parseFloat($value): ?float
    {
        $clean = str_replace([',', ' '], '', (string)$value);
        return is_numeric($clean) ? (float)$clean : null;
    }
}
