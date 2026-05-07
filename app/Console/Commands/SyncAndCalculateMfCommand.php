<?php

namespace App\Console\Commands;

use App\Services\MfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator command scheduled at 23:30 IST.
 *
 * Step 1 — Sync: delegates to sync:mf-daily (downloads AMFI NAVAll.txt,
 *           upserts mutual_funds and mutual_fund_prices).
 * Step 2 — Calculate: uses MfService to compute all 18 return columns for
 *           every fund updated today, using a preceding-date search that
 *           correctly handles weekends, holidays, and the 3y data gap.
 */
class SyncAndCalculateMfCommand extends Command
{
    protected $signature = 'mf:sync-and-calculate
                            {--skip-returns : Skip return computation after sync}
                            {--dry-run      : Pass through to sync:mf-daily; no DB writes}';

    protected $description = 'Fetch today\'s MF NAVs from AMFI then compute all performance returns';

    private MfService $mfService;

    public function __construct(MfService $mfService)
    {
        parent::__construct();
        $this->mfService = $mfService;
    }

    public function handle(): int
    {
        @ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::disableQueryLog();

        $startedAt = microtime(true);
        $this->info('[mf:sync-and-calculate] Starting...');
        Log::info('[mf:sync-and-calculate] started');

        // ── Step 1: Sync NAVs via existing command ────────────────────────────
        $this->info('[1/2] Syncing NAVs from AMFI...');

        $syncArgs = ['--force' => true, '--skip-returns' => true];
        if ($this->option('dry-run')) {
            $syncArgs['--dry-run'] = true;
        }

        $syncExit = $this->call('sync:mf-daily', $syncArgs);

        if ($syncExit !== Command::SUCCESS) {
            $this->error('NAV sync failed — aborting returns computation.');
            Log::error('[mf:sync-and-calculate] sync step failed', ['exit' => $syncExit]);
            return Command::FAILURE;
        }

        if ($this->option('dry-run') || $this->option('skip-returns')) {
            $elapsed = round(microtime(true) - $startedAt, 1);
            $this->info("Complete in {$elapsed}s (returns skipped).");
            return Command::SUCCESS;
        }

        // ── Step 2: Determine the NAV date that was just inserted ─────────────
        // Query for the most recent nav_date present in the last 2 days so we
        // target exactly what was synced, even on delayed AMFI publishes.
        $navDate = DB::table('mutual_fund_prices')
            ->where('nav_date', '>=', now('Asia/Kolkata')->subDays(2)->format('Y-m-d'))
            ->max('nav_date');

        if (!$navDate) {
            $this->warn('No recent NAV rows found — returns not computed.');
            Log::warning('[mf:sync-and-calculate] no recent nav_date found');
            return Command::SUCCESS;
        }

        // ── Step 3: Compute returns using MfService ───────────────────────────
        $this->info("[2/2] Computing returns for {$navDate} via MfService...");
        Log::info('[mf:sync-and-calculate] computing returns', ['nav_date' => $navDate]);

        try {
            $result = $this->mfService->computeReturnsForDate($navDate);

            $this->info("  Returns updated for {$result['rows_written']} funds.");
            Log::info('[mf:sync-and-calculate] returns done', [
                'nav_date'     => $navDate,
                'rows_written' => $result['rows_written'],
            ]);
        } catch (\Exception $e) {
            $this->error('Returns computation failed: ' . $e->getMessage());
            Log::error('[mf:sync-and-calculate] returns failed', [
                'nav_date' => $navDate,
                'error'    => $e->getMessage(),
            ]);
            $this->warn('Prices were saved. Returns were not computed.');
            return Command::FAILURE;
        }

        $elapsed = round(microtime(true) - $startedAt, 1);
        $this->info("[mf:sync-and-calculate] Complete in {$elapsed}s.");
        Log::info('[mf:sync-and-calculate] complete', [
            'nav_date'  => $navDate,
            'elapsed_s' => $elapsed,
        ]);

        return Command::SUCCESS;
    }
}
