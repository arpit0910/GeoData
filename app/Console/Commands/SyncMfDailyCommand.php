<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncMfDailyCommand extends Command
{
    protected $signature = 'sync:mf-daily
                            {--force       : Bypass the 9 PM–11 PM IST window guard}
                            {--dry-run     : Parse and count rows without writing to DB}
                            {--skip-returns : Skip percentage-return computation}';

    protected $description = 'Fetch AMFI NAVAll.txt and upsert mutual_funds + mutual_fund_prices';

    private const NAV_WINDOW_START = 21;
    private const NAV_WINDOW_END   = 23;
    private const AMFI_URL         = 'https://www.amfiindia.com/spages/NAVAll.txt';
    private const MASTER_CHUNK     = 500;
    private const NAV_CHUNK        = 1000;

    protected bool $shouldStop = false;

    public function handle(): int
    {
        @ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::disableQueryLog();
        $this->setupSignals();

        $startedAt = microtime(true);

        Log::info('[sync:mf-daily] started', [
            'force'        => $this->option('force'),
            'dry_run'      => $this->option('dry-run'),
            'skip_returns' => $this->option('skip-returns'),
        ]);

        if (!$this->option('force') && !$this->inNavWindow()) {
            $this->warn('Outside NAV update window (21:00–23:00 IST). Use --force to override.');
            Log::warning('[sync:mf-daily] outside NAV window — aborted');
            return Command::FAILURE;
        }

        // ── 1. Download ───────────────────────────────────────────────────────
        $this->info('[1/4] Downloading NAVAll.txt from AMFI...');
        $t = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get(self::AMFI_URL);

            if (!$response->successful()) {
                $this->error('AMFI download failed: HTTP ' . $response->status());
                Log::error('[sync:mf-daily] download failed', ['status' => $response->status()]);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('AMFI download failed: ' . $e->getMessage());
            Log::error('[sync:mf-daily] download exception', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $downloadMs = round((microtime(true) - $t) * 1000);
        $this->info(sprintf('  Downloaded %.1f KB in %dms.', strlen($response->body()) / 1024, $downloadMs));
        Log::info('[sync:mf-daily] downloaded', ['bytes' => strlen($response->body()), 'elapsed_ms' => $downloadMs]);

        // ── 2. Parse ──────────────────────────────────────────────────────────
        $this->info('[2/4] Parsing...');
        $t    = microtime(true);
        $body = $response->body();
        unset($response);
        gc_collect_cycles();

        [$masterRows, $navRows] = $this->parse($body);
        unset($body);
        gc_collect_cycles();

        $navDate  = !empty($navRows) ? $navRows[0]['nav_date'] : null;
        $parseMs  = round((microtime(true) - $t) * 1000);
        $this->info(sprintf('  Parsed %d schemes, %d NAV records. NAV date: %s (%dms)', count($masterRows), count($navRows), $navDate ?? 'none', $parseMs));
        Log::info('[sync:mf-daily] parsed', [
            'schemes'    => count($masterRows),
            'nav_rows'   => count($navRows),
            'nav_date'   => $navDate,
            'elapsed_ms' => $parseMs,
        ]);

        if ($this->option('dry-run')) {
            $this->info('[Dry-run] No writes performed.');
            Log::info('[sync:mf-daily] dry-run complete');
            return Command::SUCCESS;
        }

        // ── 3. Upsert master ──────────────────────────────────────────────────
        $this->info('[3/4] Upserting mutual_funds...');
        $t = microtime(true);
        try {
            $this->upsertMaster($masterRows);
        } catch (\Exception $e) {
            $this->error('upsertMaster failed: ' . $e->getMessage());
            Log::error('[sync:mf-daily] upsertMaster failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        unset($masterRows);
        gc_collect_cycles();
        $masterMs = round((microtime(true) - $t) * 1000);
        Log::info('[sync:mf-daily] upsertMaster done', ['elapsed_ms' => $masterMs]);

        // ── 4. Upsert prices ──────────────────────────────────────────────────
        $this->info('[4/4] Upserting mutual_fund_prices...');
        $t = microtime(true);
        try {
            $this->upsertNav($navRows);
        } catch (\Exception $e) {
            $this->error('upsertNav failed: ' . $e->getMessage());
            Log::error('[sync:mf-daily] upsertNav failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        unset($navRows);
        gc_collect_cycles();
        $navMs = round((microtime(true) - $t) * 1000);
        Log::info('[sync:mf-daily] upsertNav done', ['elapsed_ms' => $navMs]);

        // ── 5. Compute returns ────────────────────────────────────────────────
        if ($navDate && !$this->option('skip-returns')) {
            $this->info('[+] Computing returns for ' . $navDate . '...');
            $t = microtime(true);
            try {
                $this->computeReturnsForDate($navDate);
                $retMs = round((microtime(true) - $t) * 1000);
                Log::info('[sync:mf-daily] computeReturns done', ['nav_date' => $navDate, 'elapsed_ms' => $retMs]);
            } catch (\Exception $e) {
                $this->error('computeReturns failed: ' . $e->getMessage());
                Log::error('[sync:mf-daily] computeReturns failed', [
                    'nav_date' => $navDate,
                    'error'    => $e->getMessage(),
                ]);
                $this->warn('Prices saved. Returns were not computed.');
            }
        }

        $totalS = round(microtime(true) - $startedAt, 1);
        $this->info("sync:mf-daily complete ({$totalS}s).");
        Log::info('[sync:mf-daily] complete', [
            'nav_date'    => $navDate,
            'elapsed_s'   => $totalS,
            'download_ms' => $downloadMs,
            'parse_ms'    => $parseMs,
            'master_ms'   => $masterMs,
            'nav_ms'      => $navMs,
        ]);

        return Command::SUCCESS;
    }

    // ── Parsing ───────────────────────────────────────────────────────────────

    private function parse(string $body): array
    {
        $lines      = explode("\n", str_replace("\r", '', $body));
        $masterRows = [];
        $navRows    = [];

        $currentAmc      = null;
        $currentCategory = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'Scheme Code;')) {
                continue;
            }

            $fields = explode(';', $line);

            if (count($fields) === 1) {
                if (preg_match('/(?:Open Ended|Close Ended|Interval Fund) Schemes\((.+)\)/i', $line, $m)) {
                    $currentCategory = $this->parseCategory($m[1]);
                } else {
                    $currentAmc = $line;
                }
                continue;
            }

            if (count($fields) < 6) continue;

            [$schemeCode, $isinGrowth, $isinReinvest, $schemeName, $nav, $navDate] = array_map('trim', $fields);

            if (!is_numeric($schemeCode) || strlen($isinGrowth) !== 12) continue;
            if (!is_numeric($nav) || (float) $nav <= 0) continue;

            $navDateParsed = $this->parseDate($navDate);
            if (!$navDateParsed) continue;

            $masterRows[$isinGrowth] = [
                'isin'          => $isinGrowth,
                'scheme_code'   => $schemeCode,
                'isin_reinvest' => strlen($isinReinvest) === 12 ? $isinReinvest : null,
                'scheme_name'   => substr($schemeName, 0, 300),
                'amc_name'      => $currentAmc,
                'category'      => $currentCategory,
                'sub_category'  => null,
                'type'          => null,
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $navRows[] = [
                'isin'     => $isinGrowth,
                'nav_date' => $navDateParsed,
                'nav'      => (float) $nav,
            ];
        }

        return [array_values($masterRows), $navRows];
    }

    private function parseCategory(string $raw): string
    {
        $map = [
            'equity'   => 'Equity',
            'debt'     => 'Debt',
            'hybrid'   => 'Hybrid',
            'solution' => 'Solution',
            'index'    => 'Index',
            'etf'      => 'ETF',
            'fof'      => 'FoF',
        ];
        foreach ($map as $key => $label) {
            if (stripos($raw, $key) !== false) return $label;
        }
        return 'Other';
    }

    private function parseDate(string $d): ?string
    {
        try {
            return Carbon::createFromFormat('d-M-Y', $d)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // ── DB writes ─────────────────────────────────────────────────────────────

    private function upsertMaster(array $rows): void
    {
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, self::MASTER_CHUNK) as $chunk) {
            DB::table('mutual_funds')->upsert(
                $chunk,
                ['isin'],
                ['scheme_code', 'isin_reinvest', 'scheme_name', 'amc_name', 'category', 'type', 'is_active', 'updated_at']
            );
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
    }

    private function upsertNav(array $rows): void
    {
        $isinToFundId = DB::table('mutual_funds')
            ->select('*')
            ->get()
            ->mapWithKeys(fn($row) => [$row->isin => $this->resolveFundKey($row)])
            ->all();

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();
        $missingIsins = [];

        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            $insert = [];
            foreach ($chunk as $r) {
                if (!isset($isinToFundId[$r['isin']])) {
                    $missingIsins[] = $r['isin'];
                    continue;
                }
                $insert[] = [
                    'isin'     => $r['isin'],
                    'nav_date' => $r['nav_date'],
                    'nav'      => $r['nav'],
                    'mf_id'    => $isinToFundId[$r['isin']],
                ];
            }
            if ($insert) {
                DB::table('mutual_fund_prices')->upsert(
                    $insert,
                    ['isin', 'nav_date'],
                    ['mf_id', 'nav']
                );
            }
            $bar->advance(count($chunk));
            unset($insert);
        }

        if (!empty($missingIsins)) {
            $missingIsins = array_values(array_unique($missingIsins));
            $this->warn('Skipped NAV rows for ' . count($missingIsins) . ' ISINs missing from mutual_funds.');
            Log::warning('[sync:mf-daily] skipped NAV rows — missing ISINs', [
                'count'  => count($missingIsins),
                'sample' => array_slice($missingIsins, 0, 20),
            ]);
        }

        unset($isinToFundId);
        $bar->finish();
        $this->newLine();
    }

    private function resolveFundKey(object $row): int
    {
        if (isset($row->id) && is_numeric($row->id)) {
            return (int) $row->id;
        }
        return (int) $row->scheme_code;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function inNavWindow(): bool
    {
        $hour = (int) now('Asia/Kolkata')->format('G');
        return $hour >= self::NAV_WINDOW_START && $hour < self::NAV_WINDOW_END;
    }

    private function setupSignals(): void
    {
        if (!extension_loaded('pcntl')) return;
        pcntl_async_signals(true);
        $handler = function (int $sig) {
            $this->shouldStop = true;
            $label = match ($sig) { SIGTERM => 'SIGTERM', SIGINT => 'SIGINT', default => "SIG{$sig}" };
            $this->warn("\n{$label} received — stopping after current phase.");
            Log::warning('[sync:mf-daily] signal received', ['signal' => $label]);
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    // ── Returns computation ───────────────────────────────────────────────────

    private function computeReturnsForDate(string $navDate): void
    {
        $periods = [
            'chg_1d' => Carbon::parse($navDate)->subDay(),
            'chg_3d' => Carbon::parse($navDate)->subDays(3),
            'chg_7d' => Carbon::parse($navDate)->subDays(7),
            'chg_1m' => Carbon::parse($navDate)->subMonth(),
            'chg_3m' => Carbon::parse($navDate)->subMonths(3),
            'chg_6m' => Carbon::parse($navDate)->subMonths(6),
            'chg_9m' => Carbon::parse($navDate)->subMonths(9),
            'chg_1y' => Carbon::parse($navDate)->subYear(),
            'chg_3y' => Carbon::parse($navDate)->subYears(3),
        ];

        $oldest = Carbon::parse($navDate)->subYears(3)->subDays(10)->format('Y-m-d');
        $total  = DB::table('mutual_fund_prices')->where('nav_date', $navDate)->count();
        $bar    = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('mutual_fund_prices')
            ->where('nav_date', $navDate)
            ->orderBy('isin')
            ->select('isin', 'nav', 'nav_date', 'mf_id')
            ->chunk(500, function ($todayRows) use ($navDate, $oldest, $periods, $bar) {
                if ($this->shouldStop) return false; // abort chunk iteration

                $isins = $todayRows->pluck('isin')->unique()->values()->all();

                $history = DB::table('mutual_fund_prices')
                    ->whereIn('isin', $isins)
                    ->where('nav_date', '>=', $oldest)
                    ->where('nav_date', '<', $navDate)
                    ->orderBy('nav_date')
                    ->select('isin', 'nav_date', 'nav')
                    ->get()
                    ->groupBy('isin')
                    ->map(fn($rows) => $rows->values());

                $upsertRows = [];
                foreach ($todayRows as $todayRow) {
                    $schemeHistory = $history->get($todayRow->isin, collect());
                    $updates       = ['isin' => $todayRow->isin, 'nav_date' => $navDate, 'nav' => $todayRow->nav, 'mf_id' => $todayRow->mf_id];

                    foreach ($periods as $chgCol => $targetCarbon) {
                        $valCol = str_replace('chg_', 'val_', $chgCol);
                        $best   = $this->closestNav($schemeHistory, $targetCarbon);

                        if ($best && (float) $best->nav > 0) {
                            $refNav              = (float) $best->nav;
                            $updates[$chgCol]    = round((((float) $todayRow->nav - $refNav) / $refNav) * 100, 4);
                            $updates[$valCol]    = $refNav;
                        } else {
                            $updates[$chgCol] = null;
                            $updates[$valCol] = null;
                        }
                    }
                    $upsertRows[] = $updates;
                }

                if (!empty($upsertRows)) {
                    $updateCols = [];
                    foreach (array_keys($periods) as $col) {
                        $updateCols[] = $col;
                        $updateCols[] = str_replace('chg_', 'val_', $col);
                    }
                    DB::table('mutual_fund_prices')->upsert($upsertRows, ['isin', 'nav_date'], $updateCols);
                }

                $bar->advance($todayRows->count());
            });

        $bar->finish();
        $this->newLine();
        $this->info('Returns computation complete.');
    }

    private function closestNav($rows, Carbon $targetCarbon): ?object
    {
        $targetTs  = $targetCarbon->timestamp;
        $windowSec = 10 * 86400;
        $best      = null;
        $bestDiff  = PHP_INT_MAX;

        foreach ($rows as $row) {
            $diff = abs(strtotime($row->nav_date) - $targetTs);
            if ($diff <= $windowSec && $diff < $bestDiff) {
                $bestDiff = $diff;
                $best     = $row;
            }
        }

        return $best;
    }
}
