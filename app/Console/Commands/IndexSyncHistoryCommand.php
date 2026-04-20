<?php

namespace App\Console\Commands;

use App\Models\IndexPrice;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class IndexSyncHistoryCommand extends Command
{
    protected $signature = 'indices:sync-history
                            {months=36 : Months of history to sync (default: 36 = 3 years)}
                            {--exchange= : Limit to one exchange: NSE or BSE}
                            {--from= : Explicit start date YYYY-MM-DD (overrides months)}
                            {--to= : Explicit end date YYYY-MM-DD (defaults to today)}
                            {--force : Re-sync every date even if data already exists}
                            {--min-bse=20 : Min BSE index count to consider a date fully BSE-synced}
                            {--analytics-only : Skip fetching, only recalculate analytics on existing data}';

    protected $description = 'Sync 3 years of historical NSE & BSE index data (defaults to last 36 months)';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $exchange = strtoupper($this->option('exchange') ?? '');

        // ── Date range ────────────────────────────────────────────────────────
        if ($this->option('from')) {
            $startDate = Carbon::parse($this->option('from'))->startOfDay();
            $endDate   = $this->option('to')
                ? Carbon::parse($this->option('to'))->startOfDay()
                : now()->startOfDay();
            $this->info("Syncing indices: explicit range");
        } else {
            $months    = (int)$this->argument('months');
            $endDate   = now()->startOfDay();
            $startDate = now()->subMonths($months)->startOfMonth();
            $this->info("Syncing indices: last {$months} months");
        }

        $this->info("From : {$startDate->format('d M Y')}");
        $this->info("To   : {$endDate->format('d M Y')}");
        if ($exchange) $this->info("Exchange: {$exchange} only");

        $totalDays   = $startDate->diffInDays($endDate);
        $bar         = $this->output->createProgressBar($totalDays + 1);
        $currentDate = clone $startDate;
        $bar->start();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // Reconnect before every DB query. BSE sync can take several minutes
            // of HTTP work with zero DB activity, causing MySQL to silently drop
            // the idle connection (wait_timeout). This prevents "Server has gone away".
            try { DB::reconnect(); } catch (\Exception $e) {}

            // ── Skip weekends — markets are closed ───────────────────────────
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                $bar->advance();
                continue;
            }

            // ── Determine what needs syncing for this date ───────────────────
            $effectiveExchange = $exchange; // may be narrowed below

            if (!$this->option('force') && !$this->option('analytics-only')) {
                $hasNse   = IndexPrice::where('traded_date', $dateStr)
                    ->whereHas('index', fn($q) => $q->where('exchange', 'NSE'))
                    ->exists();
                $bseCount = IndexPrice::where('traded_date', $dateStr)
                    ->whereHas('index', fn($q) => $q->where('exchange', 'BSE'))
                    ->count();
                $hasBse   = $bseCount >= (int)$this->option('min-bse');

                // ── Explicit single-exchange mode ────────────────────────────
                if ($exchange === 'NSE' && $hasNse) {
                    $this->line("\n  <fg=gray>SKIP {$dateStr} — NSE already synced.</>");
                    $currentDate->addDay(); $bar->advance(); continue;
                }
                if ($exchange === 'BSE' && $hasBse) {
                    $this->line("\n  <fg=gray>SKIP {$dateStr} — BSE already synced ({$bseCount}).</>");
                    $currentDate->addDay(); $bar->advance(); continue;
                }

                // ── Both exchanges mode ──────────────────────────────────────
                if (!$exchange) {
                    if ($hasNse && $hasBse) {
                        $this->line("\n  <fg=gray>SKIP {$dateStr} — NSE & BSE ({$bseCount}) both synced.</>");
                        $currentDate->addDay(); $bar->advance(); continue;
                    }
                    // Narrow to only the missing side to avoid re-fetching good data
                    if ($hasNse && !$hasBse) {
                        $effectiveExchange = 'BSE';
                        $this->line("\n  <fg=yellow>PARTIAL {$dateStr} — NSE done, fetching BSE...</>");
                    } elseif ($hasBse && !$hasNse) {
                        $effectiveExchange = 'NSE';
                        $this->line("\n  <fg=yellow>PARTIAL {$dateStr} — BSE done, fetching NSE...</>");
                    }
                }
            }

            // ── Run sync for this date ───────────────────────────────────────
            $label = $effectiveExchange ?: 'NSE + BSE';
            $this->newLine();
            $this->info("► {$dateStr} [{$label}]");

            try {
                $exitCode = Artisan::call('indices:sync', [
                    'date'             => $dateStr,
                    'exchange'         => $effectiveExchange,
                    '--analytics-only' => $this->option('analytics-only'),
                ]);

                foreach (explode("\n", trim(Artisan::output())) as $line) {
                    if ($line !== '') $this->line("  {$line}");
                }

                if ($exitCode !== 0) {
                    $this->warn("  Sync returned non-zero for {$dateStr}.");
                }
            } catch (\Exception $e) {
                $this->error("  Fatal error for {$dateStr}: " . $e->getMessage());
            }

            $currentDate->addDay();
            $bar->advance();

            if ($currentDate->day % 7 === 0) gc_collect_cycles();

            usleep(200000); // 200ms breathing room between dates
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Historical index sync complete.");
        return Command::SUCCESS;
    }
}
