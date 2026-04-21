<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncMfDailyCommand extends Command
{
    protected $signature = 'sync:mf-daily
                            {--force : Bypass the 9 PM–11 PM IST window guard}
                            {--dry-run : Parse and count rows without writing to DB}
                            {--skip-returns : Skip percentage-return computation}';

    protected $description = 'Fetch AMFI NAVAll.txt and upsert mutual_funds + mutual_fund_prices';

    private const NAV_WINDOW_START = 21;
    private const NAV_WINDOW_END   = 23;
    private const AMFI_URL         = 'https://www.amfiindia.com/spages/NAVAll.txt';
    private const MASTER_CHUNK     = 500;
    private const NAV_CHUNK        = 500;

    public function handle(): int
    {
        // Remove all resource caps for CLI
        @ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::disableQueryLog();

        if (!$this->option('force') && !$this->inNavWindow()) {
            $this->warn('Outside NAV update window (21:00–23:00 IST). Use --force to override.');
            return Command::FAILURE;
        }

        // ── 1. Download ───────────────────────────────────────────────────────
        $this->info('Downloading NAVAll.txt from AMFI...');
        try {
            $response = Http::timeout(120)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get(self::AMFI_URL);

            if (!$response->successful()) {
                $this->error('AMFI download failed: HTTP ' . $response->status());
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('AMFI download failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // ── 2. Parse ──────────────────────────────────────────────────────────
        $this->info('Parsing...');
        $body = $response->body();
        unset($response);
        gc_collect_cycles();

        [$masterRows, $navRows] = $this->parse($body);
        unset($body);
        gc_collect_cycles();

        $navDate = !empty($navRows) ? $navRows[0]['nav_date'] : null;
        $this->info(sprintf('Parsed %d schemes, %d NAV records. NAV date: %s', count($masterRows), count($navRows), $navDate ?? 'none'));

        if ($this->option('dry-run')) {
            $this->info('[Dry-run] No writes performed.');
            return Command::SUCCESS;
        }

        // ── 3. Upsert master ──────────────────────────────────────────────────
        try {
            $this->upsertMaster($masterRows);
        } catch (\Exception $e) {
            $this->error('upsertMaster failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
        unset($masterRows);
        gc_collect_cycles();

        // ── 4. Upsert prices ──────────────────────────────────────────────────
        try {
            $this->upsertNav($navRows);
        } catch (\Exception $e) {
            $this->error('upsertNav failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
        unset($navRows);
        gc_collect_cycles();

        // ── 5. Compute returns ────────────────────────────────────────────────
        if ($navDate && !$this->option('skip-returns')) {
            try {
                $this->computeReturnsForDate($navDate);
            } catch (\Exception $e) {
                $this->error('computeReturns failed: ' . $e->getMessage());
                // Non-fatal — prices are already saved
                $this->warn('Prices saved. Returns were not computed. Re-run with the same date or fix the error above.');
            }
        }

        $this->info('sync:mf-daily complete.');
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
            if (!is_numeric($nav) || (float)$nav <= 0) continue;

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
                'nav'      => (float)$nav,
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
        $this->info('Upserting mutual_funds (' . count($rows) . ' rows)...');
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
        $this->info('Upserting mutual_fund_prices (' . count($rows) . ' rows)...');

        // Plain PHP array — no Collection object in memory
        $isinToId = DB::table('mutual_funds')->pluck('id', 'isin')->all();

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            $insert = [];
            foreach ($chunk as $r) {
                if (!isset($isinToId[$r['isin']])) continue;
                $insert[] = [
                    'isin'     => $r['isin'],
                    'nav_date' => $r['nav_date'],
                    'nav'      => $r['nav'],
                    'mf_id'    => (int) $isinToId[$r['isin']],
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

        unset($isinToId);
        $bar->finish();
        $this->newLine();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function inNavWindow(): bool
    {
        $hour = (int) now('Asia/Kolkata')->format('G');
        return $hour >= self::NAV_WINDOW_START && $hour < self::NAV_WINDOW_END;
    }

    // ── Returns computation — pure SQL, zero PHP memory overhead ──────────────

    private function computeReturnsForDate(string $navDate): void
    {
        $this->info('Computing returns for ' . $navDate . '...');

        // Disable MySQL query-level timeout for this session so the UPDATE JOINs
        // are not killed on slow shared-hosting servers.
        try {
            DB::statement('SET SESSION max_execution_time = 0');
        } catch (\Exception $e) {
            // Older MySQL / MariaDB — ignore, keep going
        }

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

        foreach ($periods as $chgCol => $targetCarbon) {
            $valCol = str_replace('chg_', 'val_', $chgCol);
            $target = $targetCarbon->format('Y-m-d');
            $from   = $targetCarbon->copy()->subDays(10)->format('Y-m-d');
            $to     = $targetCarbon->copy()->addDays(10)->format('Y-m-d');

            $this->line("  Computing {$chgCol} (ref window {$from} → {$to})...");

            DB::statement("
                UPDATE mutual_fund_prices p
                INNER JOIN (
                    SELECT p2.isin, p2.nav AS ref_nav
                    FROM mutual_fund_prices p2
                    INNER JOIN (
                        SELECT isin, MIN(ABS(DATEDIFF(nav_date, ?))) AS min_diff
                        FROM mutual_fund_prices
                        WHERE nav_date BETWEEN ? AND ?
                        GROUP BY isin
                    ) closest
                        ON p2.isin = closest.isin
                       AND ABS(DATEDIFF(p2.nav_date, ?)) = closest.min_diff
                    WHERE p2.nav_date BETWEEN ? AND ?
                    GROUP BY p2.isin
                ) ref ON p.isin = ref.isin
                SET p.`{$chgCol}` = ROUND(((p.nav - ref.ref_nav) / ref.ref_nav) * 100, 4),
                    p.`{$valCol}` = ref.ref_nav
                WHERE p.nav_date = ?
                  AND ref.ref_nav > 0
            ", [$target, $from, $to, $target, $from, $to, $navDate]);

            $this->info("  {$chgCol} / {$valCol} done.");
        }

        $this->info('Returns computation complete.');
    }
}
