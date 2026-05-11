<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MfSyncCommand extends Command
{
    protected $signature = 'mf:sync {date? : Target NAV date (Y-m-d); defaults to today IST} {--force : Re-sync even if records already exist}';

    protected $description = 'Sync daily MF NAV from AMFI and calculate all period returns in one pass';

    private const AMFI_URL     = 'https://www.amfiindia.com/spages/NAVAll.txt';
    private const MASTER_CHUNK = 500;
    private const NAV_CHUNK    = 500;

    protected bool $shouldStop = false;

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::connection()->disableQueryLog();
        $this->setupSignals();

        $targetDate = $this->argument('date') ?: now('Asia/Kolkata')->format('Y-m-d');
        $startedAt  = microtime(true);

        Log::info('[mf:sync] started', [
            'target_date' => $targetDate,
            'force'       => $this->option('force'),
        ]);

        if (!$this->option('force') && $this->isAlreadySynced($targetDate)) {
            $this->info("NAV records already exist for {$targetDate}. Job complete.");
            Log::info('[mf:sync] already synced', ['date' => $targetDate]);
            return Command::SUCCESS;
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
                Log::error('[mf:sync] download failed', ['status' => $response->status()]);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('AMFI download failed: ' . $e->getMessage());
            Log::error('[mf:sync] download exception', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $downloadMs = round((microtime(true) - $t) * 1000);
        $this->info(sprintf('  Downloaded %.1f KB in %dms.', strlen($response->body()) / 1024, $downloadMs));
        Log::info('[mf:sync] downloaded', ['bytes' => strlen($response->body()), 'elapsed_ms' => $downloadMs]);

        // ── 2. Parse ──────────────────────────────────────────────────────────
        $this->info('[2/4] Parsing...');
        $t    = microtime(true);
        $body = $response->body();
        unset($response);
        gc_collect_cycles();

        [$masterRows, $navRows, $navDate] = $this->parse($body);
        unset($body);
        gc_collect_cycles();

        $parseMs = round((microtime(true) - $t) * 1000);
        $this->info(sprintf(
            '  Parsed %d schemes, %d NAV rows. NAV date: %s (%dms)',
            count($masterRows), count($navRows), $navDate ?? 'none', $parseMs
        ));
        Log::info('[mf:sync] parsed', [
            'schemes'    => count($masterRows),
            'nav_rows'   => count($navRows),
            'nav_date'   => $navDate,
            'elapsed_ms' => $parseMs,
        ]);

        if (!$navDate || empty($navRows)) {
            $this->error('No valid NAV data found in AMFI response.');
            Log::error('[mf:sync] no valid data');
            return Command::FAILURE;
        }

        if ($navDate !== $targetDate) {
            $this->warn("AMFI NAV date ({$navDate}) differs from target ({$targetDate}). Proceeding with AMFI date.");
            Log::warning('[mf:sync] nav_date mismatch', ['nav_date' => $navDate, 'target_date' => $targetDate]);
        }

        if (!$this->option('force') && $this->isAlreadySynced($navDate)) {
            $this->info("NAV records already exist for {$navDate}. Job complete.");
            Log::info('[mf:sync] already synced after parse', ['date' => $navDate]);
            return Command::SUCCESS;
        }

        // ── 3. Upsert master ──────────────────────────────────────────────────
        $this->info('[3/4] Upserting mutual_funds...');
        $t = microtime(true);
        $this->upsertMaster($masterRows);
        unset($masterRows);
        gc_collect_cycles();
        $masterMs = round((microtime(true) - $t) * 1000);
        Log::info('[mf:sync] upsertMaster done', ['elapsed_ms' => $masterMs]);

        // ── 4. Upsert NAV prices + compute returns ────────────────────────────
        $this->info('[4/4] Upserting mutual_fund_prices...');
        $t = microtime(true);
        $this->upsertNav($navRows, $navDate);
        unset($navRows);
        gc_collect_cycles();
        $navMs = round((microtime(true) - $t) * 1000);
        Log::info('[mf:sync] upsertNav done', ['elapsed_ms' => $navMs]);

        // ── 5. Compute period returns ─────────────────────────────────────────
        $this->info("Computing period returns for {$navDate}...");
        $t           = microtime(true);
        $rowsWritten = $this->computeReturns($navDate);
        $returnsMs   = round((microtime(true) - $t) * 1000);
        $this->info(sprintf('  Returns written for %d funds (%dms).', $rowsWritten, $returnsMs));
        Log::info('[mf:sync] computeReturns done', [
            'nav_date'    => $navDate,
            'rows_written' => $rowsWritten,
            'elapsed_ms'  => $returnsMs,
        ]);

        $elapsed = round(microtime(true) - $startedAt, 1);
        $this->info("mf:sync complete for {$navDate} ({$elapsed}s).");
        Log::info('[mf:sync] complete', [
            'nav_date'     => $navDate,
            'elapsed_s'    => $elapsed,
            'download_ms'  => $downloadMs,
            'parse_ms'     => $parseMs,
            'master_ms'    => $masterMs,
            'nav_ms'       => $navMs,
            'returns_ms'   => $returnsMs,
        ]);

        return Command::SUCCESS;
    }

    // ── Parsing ───────────────────────────────────────────────────────────────

    private function parse(string $body): array
    {
        $lines           = explode("\n", str_replace("\r", '', $body));
        $masterRows      = [];
        $navRows         = [];
        $navDate         = null;
        $currentAmc      = null;
        $currentCategory = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'Scheme Code;')) continue;

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

            [$schemeCode, $isinGrowth, $isinReinvest, $schemeName, $nav, $navDateRaw] = array_map('trim', $fields);

            if (!is_numeric($schemeCode) || strlen($isinGrowth) !== 12) continue;
            if (!is_numeric($nav) || (float) $nav <= 0) continue;

            $parsedDate = $this->parseDate($navDateRaw);
            if (!$parsedDate) continue;

            if (!$navDate) $navDate = $parsedDate;

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
                'nav_date' => $parsedDate,
                'nav'      => (float) $nav,
            ];
        }

        return [array_values($masterRows), $navRows, $navDate];
    }

    // ── DB writes ─────────────────────────────────────────────────────────────

    private function upsertMaster(array $rows): void
    {
        foreach (array_chunk($rows, self::MASTER_CHUNK) as $chunk) {
            DB::table('mutual_funds')->upsert(
                $chunk,
                ['isin'],
                ['scheme_code', 'isin_reinvest', 'scheme_name', 'amc_name', 'category', 'is_active', 'updated_at']
            );
        }
        $this->info(sprintf('  Upserted %d scheme records.', count($rows)));
    }

    private function upsertNav(array $rows, string $navDate): void
    {
        $isinToId = DB::table('mutual_funds')->pluck('id', 'isin')->all();
        $missing  = [];

        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            $insert = [];
            foreach ($chunk as $r) {
                $mfId = $isinToId[$r['isin']] ?? null;
                if (!$mfId) {
                    $missing[] = $r['isin'];
                    continue;
                }
                $insert[] = [
                    'mf_id'    => $mfId,
                    'isin'     => $r['isin'],
                    'nav_date' => $r['nav_date'],
                    'nav'      => $r['nav'],
                ];
            }
            if ($insert) {
                DB::table('mutual_fund_prices')->upsert(
                    $insert,
                    ['isin', 'nav_date'],
                    ['mf_id', 'nav']
                );
            }
            unset($insert);
        }

        if (!empty($missing)) {
            $missing = array_values(array_unique($missing));
            $this->warn('  Skipped ' . count($missing) . ' ISINs not found in mutual_funds.');
            Log::warning('[mf:sync] skipped NAV rows — missing ISINs', [
                'count'  => count($missing),
                'sample' => array_slice($missing, 0, 20),
            ]);
        }

        $this->info(sprintf('  Upserted %d NAV rows for %s.', count($rows) - count($missing), $navDate));
    }

    // ── Returns computation ───────────────────────────────────────────────────

    private function computeReturns(string $navDate): int
    {
        $periodTargets = [
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

        $updateCols = [];
        foreach (array_keys($periodTargets) as $col) {
            $updateCols[] = $col;
            $updateCols[] = str_replace('chg_', 'val_', $col);
        }

        $oldest      = Carbon::parse($navDate)->subYears(3)->subDays(10)->format('Y-m-d');
        $rowsWritten = 0;

        DB::table('mutual_fund_prices')
            ->where('nav_date', $navDate)
            ->orderBy('isin')
            ->select('isin', 'nav', 'mf_id')
            ->chunk(500, function ($todayRows) use ($navDate, $oldest, $periodTargets, $updateCols, &$rowsWritten) {
                if ($this->shouldStop) return false;

                $isins = $todayRows->pluck('isin')->unique()->values()->all();

                $history = DB::table('mutual_fund_prices')
                    ->whereIn('isin', $isins)
                    ->where('nav_date', '>=', $oldest)
                    ->where('nav_date', '<', $navDate)
                    ->orderBy('nav_date')
                    ->select('isin', 'nav_date', 'nav')
                    ->get()
                    ->groupBy('isin');

                $upsertRows = [];
                foreach ($todayRows as $todayRow) {
                    $schemeHistory = $history->get($todayRow->isin, collect());
                    $row           = [
                        'mf_id'    => $todayRow->mf_id,
                        'isin'     => $todayRow->isin,
                        'nav_date' => $navDate,
                        'nav'      => $todayRow->nav,
                    ];

                    foreach ($periodTargets as $chgCol => $targetCarbon) {
                        $valCol = str_replace('chg_', 'val_', $chgCol);
                        $best   = $this->closestNav($schemeHistory, $targetCarbon);

                        if ($best && (float) $best->nav > 0) {
                            $refNav          = (float) $best->nav;
                            $row[$chgCol]    = round((((float) $todayRow->nav - $refNav) / $refNav) * 100, 4);
                            $row[$valCol]    = $refNav;
                        } else {
                            $row[$chgCol] = null;
                            $row[$valCol] = null;
                        }
                    }

                    $upsertRows[] = $row;
                }

                if (!empty($upsertRows)) {
                    DB::table('mutual_fund_prices')->upsert($upsertRows, ['isin', 'nav_date'], $updateCols);
                    $rowsWritten += count($upsertRows);
                }

                unset($history, $upsertRows);
                gc_collect_cycles();
            });

        return $rowsWritten;
    }

    private function closestNav($rows, Carbon $target): ?object
    {
        $targetTs  = $target->timestamp;
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isAlreadySynced(string $date): bool
    {
        return DB::table('mutual_fund_prices')->where('nav_date', $date)->exists();
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

    private function setupSignals(): void
    {
        if (!extension_loaded('pcntl')) return;
        pcntl_async_signals(true);
        $handler = function (int $sig) {
            $this->shouldStop = true;
            $label = match ($sig) { SIGTERM => 'SIGTERM', SIGINT => 'SIGINT', default => "SIG{$sig}" };
            $this->warn("\n{$label} received — stopping after current step.");
            Log::warning('[mf:sync] signal received', ['signal' => $label]);
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }
}
