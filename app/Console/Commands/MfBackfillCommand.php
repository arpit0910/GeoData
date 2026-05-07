<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Backfills historical MF NAVs from api.mfapi.in.
 *
 * Prerequisites: Run `sync:mf-daily --force` first to populate mutual_funds.
 *
 * Usage examples:
 *   php artisan mf:backfill                          # backfill all, skip ISINs already imported
 *   php artisan mf:backfill --fresh                  # re-import everything
 *   php artisan mf:backfill --scheme=119551          # single scheme_code
 *   php artisan mf:backfill --limit=100 --delay=100000  # smoke-test with 100 schemes
 */
class MfBackfillCommand extends Command
{
    protected $signature = 'mf:backfill
                            {--from=2023-04-01  : Earliest nav_date to store (YYYY-MM-DD)}
                            {--fresh            : Re-fetch and insert even if history already exists}
                            {--scheme=          : Process only this scheme_code}
                            {--chunk=500        : Rows per DB insertOrIgnore call}
                            {--delay=250000     : Microseconds to sleep between API calls (default 250 ms)}
                            {--limit=           : Cap the number of schemes processed (for testing)}';

    protected $description = 'Backfill historical NAV data from api.mfapi.in (stores records on or after 2023-04-01)';

    private const MFAPI_BASE   = 'https://api.mfapi.in/mf';
    private const DEFAULT_FROM = '2023-04-01';

    protected bool $shouldStop = false;

    public function handle(): int
    {
        @ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::disableQueryLog();
        $this->setupSignals();

        $fromDate  = $this->option('from')   ?: self::DEFAULT_FROM;
        $fresh     = (bool) $this->option('fresh');
        $chunkSize = max(1,  (int) ($this->option('chunk')  ?: 500));
        $delay     = max(0,  (int) ($this->option('delay')  ?: 250000));
        $schemeOpt = $this->option('scheme');
        $limit     = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info("[mf:backfill] Starting — from={$fromDate} | fresh=" . ($fresh ? 'yes' : 'no'));
        Log::info('[mf:backfill] started', compact('fromDate', 'fresh', 'chunkSize', 'delay'));

        // ── 1. Load our scheme registry ───────────────────────────────────────
        $this->info('[1/3] Loading schemes from mutual_funds...');
        $schemeQuery = DB::table('mutual_funds')->select('id', 'isin', 'scheme_code');
        if ($schemeOpt !== null) {
            $schemeQuery->where('scheme_code', $schemeOpt);
        }
        $schemes = $schemeQuery->get();

        if ($schemes->isEmpty()) {
            $this->error('mutual_funds table is empty. Run `sync:mf-daily --force` first.');
            return Command::FAILURE;
        }

        if ($limit !== null) {
            $schemes = $schemes->take($limit);
        }

        $total = $schemes->count();
        $this->info("  {$total} schemes loaded.");

        // ── 2. Build set of ISINs already backfilled (skip unless --fresh) ────
        $existingIsins = [];
        if (!$fresh) {
            // An ISIN is considered backfilled if we have any row within the first
            // 5 days after the cut-off date (proves we have early history).
            $cutoffCheck   = Carbon::parse($fromDate)->addDays(5)->format('Y-m-d');
            $existingIsins = DB::table('mutual_fund_prices')
                ->where('nav_date', '<=', $cutoffCheck)
                ->select('isin')
                ->distinct()
                ->pluck('isin')
                ->flip()  // isin => 0 — O(1) lookup via isset()
                ->all();

            $this->info('  ' . count($existingIsins) . ' ISINs already have early history (will skip).');
        }

        // ── 3. Fetch history from mfapi.in and insert ─────────────────────────
        $this->info('[2/3] Fetching from api.mfapi.in...');
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %estimated:-6s% | %message%'
        );
        $bar->setMessage('initializing...');
        $bar->start();

        $processed = 0;
        $skipped   = 0;
        $failed    = 0;
        $inserted  = 0;
        $startedAt = microtime(true);

        foreach ($schemes as $scheme) {
            if ($this->shouldStop) {
                $this->newLine();
                $this->warn("Stop signal — exiting after {$processed}/{$total}.");
                Log::warning('[mf:backfill] stopped by signal', compact('processed', 'total'));
                break;
            }

            $processed++;
            $schemeCode = (string) $scheme->scheme_code;
            $isin       = (string) $scheme->isin;
            $fundId     = (int)    $scheme->id;

            $bar->setMessage("{$schemeCode} / {$isin}");

            // Skip already-backfilled ISINs
            if (!$fresh && isset($existingIsins[$isin])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Fetch full NAV history from mfapi.in
            $apiData = $this->fetchHistory($schemeCode);
            if ($apiData === null) {
                $failed++;
                $bar->advance();
                usleep($delay);
                continue;
            }

            // Filter to from-date and build insert rows
            $rows = $this->buildNavRows($apiData, $isin, $fundId, $fromDate);
            if (!empty($rows)) {
                $inserted += $this->insertRows($rows, $chunkSize);
            }

            $bar->advance();

            if ($delay > 0) {
                usleep($delay);
            }
        }

        $bar->setMessage('done');
        $bar->finish();
        $this->newLine();

        $elapsed = round(microtime(true) - $startedAt, 1);
        $summary = "Done in {$elapsed}s — Processed: {$processed}/{$total} | Skipped: {$skipped} | Failed: {$failed} | Rows inserted: {$inserted}";
        $this->info($summary);
        Log::info('[mf:backfill] complete', compact('processed', 'total', 'skipped', 'failed', 'inserted', 'elapsed'));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // ── API ───────────────────────────────────────────────────────────────────

    /**
     * Fetch the full NAV history for one scheme from mfapi.in.
     * Returns the 'data' array on success, null on any failure.
     *
     * @return array<int, array{date: string, nav: string}>|null
     */
    private function fetchHistory(string $schemeCode): ?array
    {
        try {
            $response = Http::retry(3, 1500, throw: false)
                ->timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::MFAPI_BASE . '/' . $schemeCode);

            if (!$response->successful()) {
                Log::warning('[mf:backfill] HTTP error', [
                    'scheme' => $schemeCode,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();

            if (($json['status'] ?? '') !== 'SUCCESS') {
                Log::warning('[mf:backfill] bad status', [
                    'scheme' => $schemeCode,
                    'status' => $json['status'] ?? 'missing',
                ]);
                return null;
            }

            return $json['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('[mf:backfill] exception', [
                'scheme' => $schemeCode,
                'error'  => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Convert mfapi.in data entries to DB-ready rows, filtering by fromDate.
     *
     * mfapi.in returns dates as DD-MM-YYYY (e.g. "01-04-2023").
     *
     * @param  array<int, array{date: string, nav: string}> $apiData
     * @return array<int, array{isin: string, mf_id: int, nav_date: string, nav: float}>
     */
    private function buildNavRows(array $apiData, string $isin, int $fundId, string $fromDate): array
    {
        $cutoff = strtotime($fromDate);
        $rows   = [];

        foreach ($apiData as $entry) {
            $dateStr = $entry['date'] ?? null;
            $navStr  = $entry['nav']  ?? null;

            if (!$dateStr || !is_numeric($navStr) || (float) $navStr <= 0.0) continue;

            $parsed = $this->parseApiDate($dateStr);
            if ($parsed === null) continue;

            if (strtotime($parsed) < $cutoff) continue; // before our window

            $rows[] = [
                'isin'     => $isin,
                'mf_id'    => $fundId,
                'nav_date' => $parsed,
                'nav'      => round((float) $navStr, 4),
            ];
        }

        return $rows;
    }

    /**
     * Parse a date string from mfapi.in (DD-MM-YYYY) to Y-m-d.
     * Returns null if the format is unrecognised.
     */
    private function parseApiDate(string $d): ?string
    {
        // Primary: DD-MM-YYYY
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $d, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // Fallback: already Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }
        return null;
    }

    /**
     * Insert rows in chunks using insertOrIgnore to handle duplicates gracefully.
     */
    private function insertRows(array $rows, int $chunkSize): int
    {
        $count = 0;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table('mutual_fund_prices')->insertOrIgnore($chunk);
            $count += count($chunk);
        }
        return $count;
    }

    private function setupSignals(): void
    {
        if (!extension_loaded('pcntl')) return;
        pcntl_async_signals(true);
        $handler = function (int $sig) {
            $this->shouldStop = true;
            $label = match ($sig) { SIGTERM => 'SIGTERM', SIGINT => 'SIGINT', default => "SIG{$sig}" };
            $this->warn("\n{$label} received — will stop after the current scheme.");
            Log::warning('[mf:backfill] signal received', ['signal' => $label]);
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }
}
