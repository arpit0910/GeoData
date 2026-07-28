<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Index;
use App\Models\IndexPrice;
use App\Models\Equity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncIndices extends Command
{
    protected $signature = 'indices:sync
                            {date?  : Trading date YYYY-MM-DD (defaults to today)}
                            {exchange? : NSE or BSE (default: both)}
                            {--analytics-only : Skip fetch, just recalculate analytics for existing data}
                            {--refresh-overview : Force refresh of overview and holdings for existing rows on the target date}';

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
        $refreshOverview = (bool) $this->option('refresh-overview');

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
                $existingIndices = IndexPrice::query()
                    ->with('index:index_code,index_name,exchange')
                    ->where('traded_date', $dateStr)
                    ->when($exchange, fn($q) => $q->whereHas('index', fn($iq) => $iq->where('exchange', $exchange)))
                    ->get();

                $missingDetails = $existingIndices->filter(
                    fn($row) => $this->needsHoldingsBackfill($row)
                )->values();

                if ($refreshOverview) {
                    $this->info("  Refreshing overview and holdings for all {$existingIndices->count()} existing indices on {$dateStr}...");
                    $this->syncOverviewDetails($currentDateObj, $existingIndices);
                } elseif ($missingDetails->isNotEmpty()) {
                    $this->info("  Data already exists for {$dateStr}. Backfilling overview and holdings for {$missingDetails->count()} indices...");
                    $this->syncOverviewDetails($currentDateObj, $missingDetails);
                }

                $this->info("  Recalculating analytics for existing {$dateStr} records to keep performance returns up to date...");
                $this->calculateAnalytics($currentDateObj);

                if ($refreshOverview) {
                    $this->info("  Data already exists for {$dateStr}. Overview, holdings, and analytics refreshed.");
                } elseif ($missingDetails->isEmpty()) {
                    $this->info("  Data already exists for {$dateStr}. Analytics refreshed.");
                }

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
                $overviewExchanges = [];
                if ($syncedNse > 0) $overviewExchanges[] = 'NSE';
                if ($syncedBse > 0) $overviewExchanges[] = 'BSE';

                if (!empty($overviewExchanges)) {
                    $syncedIndices = IndexPrice::query()
                        ->with('index:index_code,index_name,exchange')
                        ->where('traded_date', $dateStr)
                        ->whereHas('index', fn($q) => $q->whereIn('exchange', $overviewExchanges))
                        ->get();

                    $this->syncOverviewDetails($currentDateObj, $syncedIndices);
                }

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

            // chg_1d — calculated from prev_close (val_1d column does not exist in schema)
            if ($price->prev_close && $price->prev_close > 0 && $price->close > 0) {
                $price->chg_1d = (($price->close - $price->prev_close) / $price->prev_close) * 100;
            }

            // Historical period returns (3d → 3y) — val_* and chg_* both exist for these
            if ($historyByDate) {
                foreach ($dateWindowMap as $key => $candidates) {
                    if ($key === '1d') continue; // handled above via prev_close
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
            ])->withoutVerifying()->timeout(30)->get($url);

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
        // 404 responses are definitive — we skip retries if every URL returned 404.
        // Only network errors or 5xx responses trigger a retry.
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
                ])->withoutVerifying()->withHeaders($headers)->timeout(10)->get($warmupPages[$attempt % count($warmupPages)]);
            } catch (\Exception $e) {
                // Warmup failure is non-fatal — carry on
            }

            $hadTransientError = false;

            foreach ($urls as $url) {
                $this->info("  [BSE] Trying: {$url}");
                try {
                    $response = Http::withOptions([
                        'cookies'         => $cookieJar,
                        'connect_timeout' => 5,   // fail fast if IP is blocked
                    ])->withoutVerifying()->withHeaders($headers)->timeout($timeout)->get($url);

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
                        $hadTransientError = true; // HTML may be a session/bot-check — worth retrying
                    } elseif ($response->status() === 404) {
                        $this->warn("  [BSE] HTTP 404 from: {$url}");
                        // 404 = file definitively missing — don't count as transient
                    } else {
                        $this->warn("  [BSE] HTTP {$response->status()} from: {$url}");
                        $hadTransientError = true; // 5xx or other — worth retrying
                    }
                } catch (\Exception $e) {
                    $this->warn("  [BSE] {$url}: " . $e->getMessage());
                    $hadTransientError = true; // connection error — worth retrying
                }
            }

            // All URLs returned 404 — retrying will not help; bail out early.
            if (!$hadTransientError) {
                $this->warn("  [BSE] All URLs returned 404 — no retry needed.");
                break;
            }
        }

        if (!$csvData) {
            $this->warn("  [BSE] All direct URLs failed. Trying Yahoo Finance...");
            $count = $this->syncBseViaYahoo($date);
            if ($count > 0) return $count;

            $this->warn("  [BSE] Yahoo Finance returned no data. Trying Stooq...");
            return $this->syncBseViaStooq($date);
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
        // Only tickers confirmed to return price data via Yahoo Finance.
        // BSE sectoral tickers (BSE-BANK.BO, BSE-IT.BO, etc.) are listed on Yahoo
        // but return zero rows for all dates — removed to avoid wasted requests.
        $tickers = [
            '^BSESN'        => ['name' => 'S&P BSE SENSEX',   'code' => 'BSE_SENSEX',   'cat' => 'Broad-based'],
            'BSE-100.BO'    => ['name' => 'S&P BSE 100',       'code' => 'BSE_100',      'cat' => 'Broad-based'],
            'BSE-200.BO'    => ['name' => 'S&P BSE 200',       'code' => 'BSE_200',      'cat' => 'Broad-based'],
            'BSE-500.BO'    => ['name' => 'S&P BSE 500',       'code' => 'BSE_500',      'cat' => 'Broad-based'],
            'BSE-MidCap.BO' => ['name' => 'S&P BSE MidCap',   'code' => 'BSE_MIDCAP',   'cat' => 'Broad-based'],
            'BSE-SmlCap.BO' => ['name' => 'S&P BSE SmallCap', 'code' => 'BSE_SMALLCAP', 'cat' => 'Broad-based'],
        ];

        $pricesData  = [];
        $indicesData = [];
        $now         = now();
        $dateStr     = $date->format('Y-m-d');
        $tsStart     = $date->copy()->startOfDay()->timestamp;
        $tsEnd       = $date->copy()->endOfDay()->timestamp;

        // ── Connectivity check ───────────────────────────────────────────────
        // query1 is often blocked on datacenter IPs. query2 is a mirror that may not be.
        // We probe ONCE with the first ticker instead of testing per-ticker —
        // if both hosts time out (5s each), we bail immediately rather than burning
        // connect_timeout × 6 tickers = ~30–60s of wasted wait.
        $yahooHosts  = ['query1.finance.yahoo.com', 'query2.finance.yahoo.com'];
        $workingHost = null;
        $firstSymbol = array_key_first($tickers);
        $probeUrl    = "https://%s/v8/finance/chart/{$firstSymbol}?period1={$tsStart}&period2={$tsEnd}&interval=1d";

        foreach ($yahooHosts as $host) {
            try {
                $probe = Http::withOptions(['connect_timeout' => 5])
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->timeout(10)
                    ->get(sprintf($probeUrl, $host));
                // Any HTTP response (even 404) means the host is reachable
                $workingHost = $host;
                $this->info("  [Yahoo] Reachable via {$host}.");
                break;
            } catch (\Exception $e) {
                $this->warn("  [Yahoo/{$host}] unreachable: " . $e->getMessage());
            }
        }

        if (!$workingHost) {
            $this->warn("  [Yahoo] Both endpoints unreachable — Yahoo Finance appears blocked on this server.");
            return 0;
        }

        // ── Fetch all tickers via the confirmed reachable host ────────────────
        foreach ($tickers as $ticker => $meta) {
            $this->info("  [Yahoo] {$meta['name']}...");
            try {
                $url = "https://{$workingHost}/v8/finance/chart/{$ticker}"
                     . "?period1={$tsStart}&period2={$tsEnd}&interval=1d";

                $response = Http::withOptions(['connect_timeout' => 5])
                    ->withoutVerifying()
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

    // ── BSE Stooq fallback ───────────────────────────────────────────────────

    private function syncBseViaStooq(Carbon $date): int
    {
        // Stooq.com — free historical data, no auth, accessible from most servers.
        // Each request returns a CSV with Date,Open,High,Low,Close,Volume.
        // If the requested date is a holiday, Stooq returns the previous trading day —
        // we check the returned date and skip if it doesn't match.
        $tickers = [
            '^bsesn'     => ['name' => 'S&P BSE SENSEX',   'code' => 'BSE_SENSEX',   'cat' => 'Broad-based'],
            'bse100.in'  => ['name' => 'S&P BSE 100',       'code' => 'BSE_100',      'cat' => 'Broad-based'],
            'bse200.in'  => ['name' => 'S&P BSE 200',       'code' => 'BSE_200',      'cat' => 'Broad-based'],
            'bse500.in'  => ['name' => 'S&P BSE 500',       'code' => 'BSE_500',      'cat' => 'Broad-based'],
            'bsemi.in'   => ['name' => 'S&P BSE MidCap',   'code' => 'BSE_MIDCAP',   'cat' => 'Broad-based'],
            'bsesi.in'   => ['name' => 'S&P BSE SmallCap', 'code' => 'BSE_SMALLCAP', 'cat' => 'Broad-based'],
        ];

        $pricesData  = [];
        $indicesData = [];
        $now         = now();
        $dateStr     = $date->format('Y-m-d');
        $d1          = $date->format('Ymd');

        foreach ($tickers as $symbol => $meta) {
            $this->info("  [Stooq] {$meta['name']}...");
            try {
                $url = 'https://stooq.com/q/d/l/?s=' . urlencode($symbol)
                     . "&d1={$d1}&d2={$d1}&i=d";

                $response = Http::withOptions(['connect_timeout' => 5])
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->timeout(10)
                    ->get($url);

                if (!$response->successful()) {
                    $this->warn("  [Stooq] HTTP {$response->status()} for {$symbol}");
                    continue;
                }

                $body = trim($response->body());

                // Stooq returns plain text "No data" or a Polish error when symbol
                // is unknown or the exchange is closed for the period requested.
                if (
                    stripos($body, 'No data') !== false ||
                    stripos($body, 'Przekroczono') !== false ||
                    strlen($body) < 10
                ) {
                    $this->warn("  [Stooq] No data for {$symbol} on {$dateStr}");
                    continue;
                }

                $lines = explode("\n", str_replace("\r", '', $body));
                if (count($lines) < 2) continue;

                array_shift($lines); // skip header (Date,Open,High,Low,Close,Volume)
                $row = str_getcsv(trim($lines[0] ?? ''));
                if (count($row) < 5) continue;

                [$rowDate, $open, $high, $low, $close] = $row;
                $volume = $row[5] ?? 0;

                // Stooq silently returns the nearest prior trading day when the
                // requested date is a holiday — reject mismatched dates.
                if (trim($rowDate) !== $dateStr) {
                    $this->warn("  [Stooq] {$symbol}: returned {$rowDate}, expected {$dateStr} — likely a holiday.");
                    continue;
                }

                $closeVal = (float)$close;
                if ($closeVal <= 0) continue;

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
                    'open'           => (float)$open,
                    'high'           => (float)$high,
                    'low'            => (float)$low,
                    'close'          => $closeVal,
                    'prev_close'     => null,
                    'change_percent' => null,
                    'volume'         => (float)$volume,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

            } catch (\Exception $e) {
                $this->warn("  [Stooq] {$symbol}: " . $e->getMessage());
            }
        }

        if (empty($indicesData)) {
            $this->warn("  [Stooq] No BSE data retrieved.");
            return 0;
        }

        Index::upsert($indicesData, ['index_code'], ['index_name', 'updated_at']);
        IndexPrice::upsert($pricesData, ['index_code', 'traded_date'], [
            'open', 'high', 'low', 'close', 'prev_close', 'change_percent', 'volume', 'updated_at',
        ]);

        $this->info("  [Stooq] Saved " . count($pricesData) . " BSE indices.");
        return count($pricesData);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function syncOverviewDetails(Carbon $date, $indices): void
    {
        $endpoint = config('services.financial_api.overview_endpoint');
        $indices = collect($indices)
            ->filter(fn($item) => !empty($item->index_code))
            ->unique('index_code')
            ->values();

        if ($indices->isEmpty()) {
            $this->warn("  [Overview] No indices available for overview sync.");
            return;
        }

        $dateStr     = $date->format('Y-m-d');
        $batchSize   = 15;
        $delayMicros = 500000;
        $chunks      = $indices->chunk($batchSize)->values();
        $useApi      = !empty($endpoint);

        if (!$useApi) {
            $this->warn("  [Overview] Missing services.financial_api.overview_endpoint config. Falling back to derived overview payloads.");
        }

        $this->info("  [Overview] Fetching detailed overview data for {$indices->count()} indices...");

        foreach ($chunks as $batchNumber => $chunk) {
            $updates = [];

            foreach ($chunk as $indexPrice) {
                try {
                    $overview = null;
                    $holdings = $this->fetchDirectExchangeHoldings($indexPrice);

                    if ($useApi) {
                        $response = Http::acceptJson()
                            ->withoutVerifying()
                            ->timeout(20)
                            ->retry(2, 500)
                            ->get($endpoint, [
                                'index_code' => $indexPrice->index_code,
                                'date' => $dateStr,
                            ]);

                        if ($response->successful()) {
                            $payload = $response->json();
                            if (is_array($payload)) {
                                $overview = $this->normalizeOverviewPayload($payload, $indexPrice, $holdings);

                                if (
                                    !$this->isValidOverviewPayload($overview)
                                    || !$this->overviewMatchesIndex($overview, $indexPrice)
                                ) {
                                    $overview = null;
                                } else {
                                    $apiHoldings = $this->extractHoldingsFromOverview($payload);
                                    if (empty($holdings) && !empty($apiHoldings)) {
                                        $holdings = $apiHoldings;
                                    }
                                }
                            }

                            if ($overview) {
                                if (empty($holdings)) {
                                    $holdings = $this->extractHoldingsFromOverview($overview);
                                }
                            } else {
                                if (!empty($holdings)) {
                                    $this->warn("  [Overview] {$indexPrice->index_code}: partial overview payload detected. Saving holdings with fallback overview.");
                                } else {
                                    $this->warn("  [Overview] {$indexPrice->index_code}: invalid or mismatched overview payload schema. Using derived fallback.");
                                }
                            }
                        } else {
                            $this->warn("  [Overview] {$indexPrice->index_code}: HTTP {$response->status()} from overview endpoint. Using derived fallback.");
                        }
                    }

                    $overview ??= $this->buildFallbackOverviewPayload($indexPrice, $holdings);
                    $holdings = !empty($holdings)
                        ? $holdings
                        : $this->extractHoldingsFromOverview($overview);

                    $updates[] = [
                        'index_code' => $indexPrice->index_code,
                        'traded_date' => $dateStr,
                        'overview' => json_encode($overview, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'holdings' => json_encode($holdings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ];
                } catch (\Exception $e) {
                    $this->warn("  [Overview] {$indexPrice->index_code}: {$e->getMessage()}. Using derived fallback.");

                    $updates[] = [
                        'index_code' => $indexPrice->index_code,
                        'traded_date' => $dateStr,
                        'overview' => json_encode($this->buildFallbackOverviewPayload($indexPrice), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'holdings' => json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($updates)) {
                IndexPrice::upsert(
                    $updates,
                    ['index_code', 'traded_date'],
                    ['overview', 'holdings', 'updated_at']
                );
            }

            $this->info("  [Overview] Batch " . ($batchNumber + 1) . " processed (" . count($updates) . " saved).");

            if (($batchNumber + 1) < $chunks->count()) {
                usleep($delayMicros);
            }
        }
    }

    private function buildFallbackOverviewPayload(IndexPrice $indexPrice, array $holdings = []): array
    {
        $index = $indexPrice->index;
        $close = $this->toFloat($indexPrice->close);
        $prevClose = $this->toFloat($indexPrice->prev_close);
        $open = $this->toFloat($indexPrice->open);
        $high = $this->toFloat($indexPrice->high);
        $low = $this->toFloat($indexPrice->low);
        $volume = $this->toFloat($indexPrice->volume);
        $turnover = $this->toFloat($indexPrice->turnover);
        $peRatio = $this->toFloat($indexPrice->pe_ratio);
        $pbRatio = $this->toFloat($indexPrice->pb_ratio);
        $divYield = $this->toFloat($indexPrice->div_yield);
        $absoluteChange = $prevClose > 0 ? round($close - $prevClose, 2) : 0.0;
        $percentageChange = $prevClose > 0 ? round((($close - $prevClose) / $prevClose) * 100, 4) : $this->toFloat($indexPrice->change_percent);

        return [
            'index_metadata' => [
                'name' => (string) ($index->index_name ?? $indexPrice->index_code),
                'ticker' => (string) $indexPrice->index_code,
                'exchange' => (string) ($index->exchange ?? ''),
                'currency' => 'INR',
            ],
            'daily_price_summary' => [
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'previous_close' => $prevClose,
                'absolute_change' => $absoluteChange,
                'percentage_change' => $percentageChange,
                'volume' => $volume,
                'turnover_in_crores' => $turnover,
                'fifty_two_week_high' => 0.0,
                'fifty_two_week_low' => 0.0,
            ],
            'valuation_metrics' => [
                'pe_ratio' => $peRatio,
                'pb_ratio' => $pbRatio,
                'dividend_yield_percentage' => $divYield,
            ],
            'market_capitalization' => [
                'total_market_cap' => 0.0,
                'free_float_market_cap' => 0.0,
            ],
            'composition_overview' => [
                'total_constituents' => count($holdings),
                'top_constituents' => $holdings,
                'sector_weightages' => [],
            ],
            'risk_metrics' => [
                'beta' => 0.0,
                'standard_deviation' => 0.0,
                'correlation' => 0.0,
            ],
        ];
    }

    private function isValidOverviewPayload(array $overview): bool
    {
        $requiredSections = [
            'index_metadata',
            'daily_price_summary',
            'valuation_metrics',
            'market_capitalization',
            'composition_overview',
            'risk_metrics',
        ];

        foreach ($requiredSections as $section) {
            if (!isset($overview[$section]) || !is_array($overview[$section])) {
                return false;
            }
        }

        if (!$this->hasRequiredKeys($overview['index_metadata'], ['name', 'ticker', 'exchange', 'currency'])) {
            return false;
        }

        foreach (['name', 'ticker', 'exchange', 'currency'] as $field) {
            if (!is_string($overview['index_metadata'][$field])) {
                return false;
            }
        }

        if (
            !$this->validateNumericFields($overview['daily_price_summary'], [
                'open', 'high', 'low', 'close', 'previous_close', 'absolute_change',
                'percentage_change', 'volume', 'turnover_in_crores',
                'fifty_two_week_high', 'fifty_two_week_low',
            ]) ||
            !$this->validateNumericFields($overview['valuation_metrics'], [
                'pe_ratio', 'pb_ratio', 'dividend_yield_percentage',
            ]) ||
            !$this->validateNumericFields($overview['market_capitalization'], [
                'total_market_cap', 'free_float_market_cap',
            ]) ||
            !$this->validateNumericFields($overview['risk_metrics'], [
                'beta', 'standard_deviation', 'correlation',
            ])
        ) {
            return false;
        }

        $composition = $overview['composition_overview'];
        if (
            !$this->hasRequiredKeys($composition, ['total_constituents', 'top_constituents', 'sector_weightages']) ||
            !is_numeric($composition['total_constituents']) ||
            !is_array($composition['top_constituents']) ||
            !is_array($composition['sector_weightages'])
        ) {
            return false;
        }

        foreach ($composition['top_constituents'] as $constituent) {
            if (
                !is_array($constituent) ||
                !$this->hasRequiredKeys($constituent, ['company_name', 'symbol', 'weightage_percentage']) ||
                !is_string($constituent['company_name']) ||
                !is_string($constituent['symbol']) ||
                !is_numeric($constituent['weightage_percentage'])
            ) {
                return false;
            }
        }

        foreach ($composition['sector_weightages'] as $sector => $weightage) {
            if (!is_string((string) $sector) || !is_numeric($weightage)) {
                return false;
            }
        }

        return true;
    }

    private function overviewMatchesIndex(array $overview, IndexPrice $indexPrice): bool
    {
        $expectedCode = strtoupper(trim((string) $indexPrice->index_code));
        $expectedName = strtoupper(trim((string) optional($indexPrice->index)->index_name));

        $metadata = $overview['index_metadata'] ?? [];
        $ticker = strtoupper(trim((string) ($metadata['ticker'] ?? '')));
        $name = strtoupper(trim((string) ($metadata['name'] ?? '')));

        if ($ticker !== '' && $expectedCode !== '' && $ticker === $expectedCode) {
            return true;
        }

        if ($name !== '' && $expectedName !== '' && $name === $expectedName) {
            return true;
        }

        if ($ticker !== '' && $expectedName !== '' && str_contains($expectedName, $ticker)) {
            return true;
        }

        if ($name !== '' && $expectedCode !== '' && str_contains($name, $expectedCode)) {
            return true;
        }

        return $ticker === '' && $name === '';
    }

    private function normalizeOverviewPayload(array $payload, IndexPrice $indexPrice, array $holdings = []): array
    {
        if ($this->isValidOverviewPayload($payload)) {
            return $payload;
        }

        $source = $this->findNestedArray($payload, [
            ['data'],
            ['result'],
            ['overview'],
            ['data', 'overview'],
            ['data', 'result'],
        ]) ?? $payload;

        $fallback = $this->buildFallbackOverviewPayload($indexPrice, $holdings);

        $metadata = is_array($source['index_metadata'] ?? null) ? $source['index_metadata'] : [];
        $daily = is_array($source['daily_price_summary'] ?? null) ? $source['daily_price_summary'] : [];
        $valuation = is_array($source['valuation_metrics'] ?? null) ? $source['valuation_metrics'] : [];
        $marketCap = is_array($source['market_capitalization'] ?? null) ? $source['market_capitalization'] : [];
        $risk = is_array($source['risk_metrics'] ?? null) ? $source['risk_metrics'] : [];
        $composition = is_array($source['composition_overview'] ?? null) ? $source['composition_overview'] : [];

        $sectorWeightages = $this->extractSectorWeightages($source);

        return [
            'index_metadata' => [
                'name' => (string) ($metadata['name'] ?? $source['index_name'] ?? $source['name'] ?? $fallback['index_metadata']['name']),
                'ticker' => (string) ($metadata['ticker'] ?? $source['ticker'] ?? $source['index_code'] ?? $fallback['index_metadata']['ticker']),
                'exchange' => (string) ($metadata['exchange'] ?? $source['exchange'] ?? $fallback['index_metadata']['exchange']),
                'currency' => (string) ($metadata['currency'] ?? $source['currency'] ?? $fallback['index_metadata']['currency']),
            ],
            'daily_price_summary' => [
                'open' => $this->firstNumericValue($daily, ['open'], $fallback['daily_price_summary']['open']),
                'high' => $this->firstNumericValue($daily, ['high'], $fallback['daily_price_summary']['high']),
                'low' => $this->firstNumericValue($daily, ['low'], $fallback['daily_price_summary']['low']),
                'close' => $this->firstNumericValue($daily, ['close', 'last_price'], $fallback['daily_price_summary']['close']),
                'previous_close' => $this->firstNumericValue($daily, ['previous_close', 'prev_close'], $fallback['daily_price_summary']['previous_close']),
                'absolute_change' => $this->firstNumericValue($daily, ['absolute_change', 'change'], $fallback['daily_price_summary']['absolute_change']),
                'percentage_change' => $this->firstNumericValue($daily, ['percentage_change', 'change_percent', 'percent_change'], $fallback['daily_price_summary']['percentage_change']),
                'volume' => $this->firstNumericValue($daily, ['volume'], $fallback['daily_price_summary']['volume']),
                'turnover_in_crores' => $this->firstNumericValue($daily, ['turnover_in_crores', 'turnover'], $fallback['daily_price_summary']['turnover_in_crores']),
                'fifty_two_week_high' => $this->firstNumericValue($daily, ['fifty_two_week_high', '52_week_high'], $fallback['daily_price_summary']['fifty_two_week_high']),
                'fifty_two_week_low' => $this->firstNumericValue($daily, ['fifty_two_week_low', '52_week_low'], $fallback['daily_price_summary']['fifty_two_week_low']),
            ],
            'valuation_metrics' => [
                'pe_ratio' => $this->firstNumericValue($valuation, ['pe_ratio', 'pe'], $fallback['valuation_metrics']['pe_ratio']),
                'pb_ratio' => $this->firstNumericValue($valuation, ['pb_ratio', 'pb'], $fallback['valuation_metrics']['pb_ratio']),
                'dividend_yield_percentage' => $this->firstNumericValue($valuation, ['dividend_yield_percentage', 'div_yield', 'yield'], $fallback['valuation_metrics']['dividend_yield_percentage']),
            ],
            'market_capitalization' => [
                'total_market_cap' => $this->firstNumericValue($marketCap, ['total_market_cap', 'market_cap'], $fallback['market_capitalization']['total_market_cap']),
                'free_float_market_cap' => $this->firstNumericValue($marketCap, ['free_float_market_cap', 'free_float'], $fallback['market_capitalization']['free_float_market_cap']),
            ],
            'composition_overview' => [
                'total_constituents' => $this->firstNumericValue($composition, ['total_constituents', 'constituents_count', 'count'], count($holdings)),
                'top_constituents' => $holdings,
                'sector_weightages' => $sectorWeightages,
            ],
            'risk_metrics' => [
                'beta' => $this->firstNumericValue($risk, ['beta'], $fallback['risk_metrics']['beta']),
                'standard_deviation' => $this->firstNumericValue($risk, ['standard_deviation', 'std_dev'], $fallback['risk_metrics']['standard_deviation']),
                'correlation' => $this->firstNumericValue($risk, ['correlation'], $fallback['risk_metrics']['correlation']),
            ],
        ];
    }

    private function extractHoldingsFromOverview(array $overview): array
    {
        $candidates = [
            ['composition_overview', 'top_constituents'],
            ['composition_overview', 'constituents'],
            ['composition', 'top_constituents'],
            ['composition', 'constituents'],
            ['data', 'composition_overview', 'top_constituents'],
            ['data', 'composition_overview', 'constituents'],
            ['data', 'holdings'],
            ['data', 'constituents'],
            ['holdings'],
            ['constituents'],
            ['top_holdings'],
        ];

        $holdings = [];
        foreach ($candidates as $path) {
            $candidate = $this->findNestedValue($overview, $path);
            if (is_array($candidate) && !empty($candidate)) {
                $holdings = $candidate;
                break;
            }
        }

        if (empty($holdings)) {
            return [];
        }

        $normalized = [];
        foreach ($holdings as $holding) {
            if (!is_array($holding)) {
                continue;
            }

            $companyName = $holding['company_name']
                ?? $holding['company']
                ?? $holding['name']
                ?? $holding['security_name']
                ?? $holding['constituent_name']
                ?? null;

            $symbol = $holding['symbol']
                ?? $holding['ticker']
                ?? $holding['code']
                ?? $holding['security_code']
                ?? null;

            $weight = $holding['weightage_percentage']
                ?? $holding['weightage']
                ?? $holding['weight_percent']
                ?? $holding['weight']
                ?? null;

            if (!is_string($companyName) || !is_string($symbol) || !is_numeric($weight)) {
                continue;
            }

            $normalized[] = [
                'company_name' => trim($companyName),
                'symbol' => trim($symbol),
                'weightage_percentage' => round((float) $weight, 4),
            ];
        }

        return array_values($normalized);
    }

    private function extractSectorWeightages(array $payload): array
    {
        $candidates = [
            ['composition_overview', 'sector_weightages'],
            ['composition', 'sector_weightages'],
            ['data', 'composition_overview', 'sector_weightages'],
            ['data', 'sector_weightages'],
            ['sector_weightages'],
            ['sectors'],
        ];

        foreach ($candidates as $path) {
            $candidate = $this->findNestedValue($payload, $path);
            if (!is_array($candidate)) {
                continue;
            }

            $normalized = [];
            foreach ($candidate as $key => $value) {
                if (is_array($value)) {
                    $sector = $value['sector'] ?? $value['name'] ?? $key;
                    $weight = $value['weight'] ?? $value['weightage'] ?? $value['weightage_percentage'] ?? null;
                } else {
                    $sector = $key;
                    $weight = $value;
                }

                if (!is_string((string) $sector) || !is_numeric($weight)) {
                    continue;
                }

                $normalized[(string) $sector] = round((float) $weight, 4);
            }

            if (!empty($normalized)) {
                return $normalized;
            }
        }

        return [];
    }

    private function fetchDirectExchangeHoldings(IndexPrice $indexPrice): array
    {
        $exchange = strtoupper((string) optional($indexPrice->index)->exchange);

        return match ($exchange) {
            'NSE' => $this->fetchNseHoldings($indexPrice),
            'BSE' => $this->fetchBseHoldings($indexPrice),
            default => [],
        };
    }

    private function fetchNseHoldings(IndexPrice $indexPrice): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
            'Referer' => 'https://www.niftyindices.com/reports/daily-reports',
            'Accept' => 'text/csv,text/plain,*/*',
        ];

        foreach ($this->buildNseConstituentUrls($indexPrice) as $url) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(20)
                    ->retry(1, 300)
                    ->get($url);

                if (!$response->successful()) {
                    continue;
                }

                $body = trim((string) $response->body());
                if ($body === '' || str_starts_with(strtolower($body), '<!doctype') || str_starts_with(strtolower($body), '<html')) {
                    continue;
                }

                $holdings = $this->parseNseConstituentCsv($body);
                if (!empty($holdings)) {
                    return $holdings;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [];
    }

    private function buildNseConstituentUrls(IndexPrice $indexPrice): array
    {
        $name = (string) optional($indexPrice->index)->index_name;
        $normalizedName = strtolower($name);
        $normalizedName = str_replace('&', 'and', $normalizedName);
        $normalizedName = preg_replace('/[^a-z0-9]+/', '', $normalizedName);
        $normalizedName = str_replace('index', '', $normalizedName);

        $variants = array_values(array_unique(array_filter([
            $normalizedName,
            strtolower(preg_replace('/[^a-z0-9]+/', '', str_replace('_', ' ', (string) $indexPrice->index_code))),
        ])));

        $urls = [];
        foreach ($variants as $variant) {
            $urls[] = "https://nsearchives.nseindia.com/content/indices/ind_{$variant}list.csv";
        }

        return array_values(array_unique($urls));
    }

    private function parseNseConstituentCsv(string $csvData): array
    {
        $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);
        $lines = array_values(array_filter(explode("\n", str_replace("\r", "", $csvData))));
        if (count($lines) < 2) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $map = array_flip(array_map('trim', $header));
        $nameKey = $map['Company Name'] ?? $map['Company'] ?? null;
        $symbolKey = $map['Symbol'] ?? $map['SYMBOL'] ?? null;

        if ($nameKey === null || $symbolKey === null) {
            return [];
        }

        $rows = [];
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $company = trim((string) ($row[$nameKey] ?? ''));
            $symbol = trim((string) ($row[$symbolKey] ?? ''));
            if ($company === '' || $symbol === '') {
                continue;
            }

            $rows[] = [
                'company_name' => $company,
                'symbol' => $symbol,
            ];
        }

        return $this->applyEqualWeightage($rows);
    }

    private function fetchBseHoldings(IndexPrice $indexPrice): array
    {
        $holdings = $this->fetchBseHoldingsFromWorkbook($indexPrice);
        if (!empty($holdings)) {
            return $holdings;
        }

        return $this->fetchBseHoldingsFromMobilePage($indexPrice);
    }

    private function fetchBseHoldingsFromWorkbook(IndexPrice $indexPrice): array
    {
        $url = $this->getBseWorkbookUrl($indexPrice);
        if (!$url) {
            return [];
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.bseindia.com/',
                ])
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'bse_holdings_');
            if ($tempFile === false) {
                return [];
            }

            $xlsxFile = $tempFile . '.xlsx';
            file_put_contents($xlsxFile, $response->body());
            @unlink($tempFile);

            $sheetRows = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')
                ->load($xlsxFile)
                ->getActiveSheet()
                ->toArray(null, true, true, true);

            @unlink($xlsxFile);

            $isinRows = [];
            foreach ($sheetRows as $row) {
                $isin = trim((string) ($row['C'] ?? ''));
                if (strlen($isin) === 12) {
                    $isinRows[] = $isin;
                }
            }

            $symbolMap = Equity::query()
                ->whereIn('isin', array_values(array_unique($isinRows)))
                ->get(['isin', 'bse_symbol', 'nse_symbol'])
                ->mapWithKeys(function ($equity) {
                    return [
                        $equity->isin => $equity->bse_symbol ?: $equity->nse_symbol,
                    ];
                });

            $rows = [];
            $headerFound = false;
            foreach ($sheetRows as $row) {
                if (!$headerFound) {
                    if (strtoupper(trim((string) ($row['A'] ?? ''))) === 'SCRIP_CODE') {
                        $headerFound = true;
                    }
                    continue;
                }

                $company = trim((string) ($row['B'] ?? ''));
                $isin = trim((string) ($row['C'] ?? ''));
                $symbol = trim((string) ($symbolMap[$isin] ?? ''));
                if ($company === '' || $symbol === '') {
                    continue;
                }

                $rows[] = [
                    'company_name' => $company,
                    'symbol' => $symbol,
                ];
            }

            return $this->applyEqualWeightage($rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function fetchBseHoldingsFromMobilePage(IndexPrice $indexPrice): array
    {
        $url = $this->getBseMobileConstituentUrl($indexPrice);
        if (!$url) {
            return [];
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                    'Referer' => 'https://m.bseindia.com/',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->timeout(20)
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            return $this->parseBseMobileConstituents((string) $response->body());
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getBseWorkbookUrl(IndexPrice $indexPrice): ?string
    {
        $name = strtoupper((string) optional($indexPrice->index)->index_name);

        if (str_contains($name, 'SENSEX')) {
            return 'https://www.bseindia.com/downloads1/List_of_S%26P_BSE_SENSEX_Securities.xlsx';
        }

        return null;
    }

    private function getBseMobileConstituentUrl(IndexPrice $indexPrice): ?string
    {
        $name = strtoupper((string) optional($indexPrice->index)->index_name);

        if (str_contains($name, 'SENSEX')) {
            return 'https://m.bseindia.com/Sensex.aspx?indexcode=16';
        }

        return null;
    }

    private function parseBseMobileConstituents(string $html): array
    {
        if ($html === '' || !class_exists(\DOMDocument::class)) {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if (!$loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query("//table[@id='grd1']//tr[position()>1]");
        if ($rows === false) {
            return [];
        }

        $holdings = [];
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 1) {
                continue;
            }

            $symbol = trim((string) $cells->item(0)?->textContent);
            if ($symbol === '') {
                continue;
            }

            $holdings[] = [
                'company_name' => $symbol,
                'symbol' => $symbol,
            ];
        }

        return $this->applyEqualWeightage($holdings);
    }

    private function applyEqualWeightage(array $rows): array
    {
        $rows = array_values(array_filter($rows, function ($row) {
            return is_array($row)
                && !empty(trim((string) ($row['company_name'] ?? '')))
                && !empty(trim((string) ($row['symbol'] ?? '')));
        }));

        $count = count($rows);
        if ($count === 0) {
            return [];
        }

        $weight = round(100 / $count, 4);

        return array_map(function ($row) use ($weight) {
            return [
                'company_name' => trim((string) $row['company_name']),
                'symbol' => trim((string) $row['symbol']),
                'weightage_percentage' => $weight,
            ];
        }, $rows);
    }

    private function needsHoldingsBackfill(IndexPrice $indexPrice): bool
    {
        if (empty($indexPrice->overview)) {
            return true;
        }

        $holdings = $indexPrice->holdings;
        if (!is_array($holdings) || empty($holdings)) {
            return true;
        }

        foreach ($holdings as $holding) {
            $symbol = $holding['symbol'] ?? null;
            if (!is_string($symbol) || trim($symbol) === '') {
                return true;
            }

            if (preg_match('/^\d+$/', trim($symbol))) {
                return true;
            }
        }

        return false;
    }

    private function hasRequiredKeys(array $payload, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                return false;
            }
        }

        return true;
    }

    private function validateNumericFields(array $payload, array $keys): bool
    {
        if (!$this->hasRequiredKeys($payload, $keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if (!is_numeric($payload[$key])) {
                return false;
            }
        }

        return true;
    }

    private function toFloat($value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function firstNumericValue(array $payload, array $keys, float $default = 0.0): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        return $default;
    }

    private function findNestedArray(array $payload, array $paths): ?array
    {
        foreach ($paths as $path) {
            $value = $this->findNestedValue($payload, $path);
            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function findNestedValue(array $payload, array $path): mixed
    {
        $current = $payload;
        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

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
