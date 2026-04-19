<?php

namespace App\Console\Commands;

use App\Models\IndexPrice;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class IndexSyncHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:sync-history
                            {months=12 : Number of months to sync back (ignored when --from is set)}
                            {exchange? : Optional exchange (NSE or BSE)}
                            {--from= : Explicit start date YYYY-MM-DD (overrides months)}
                            {--to= : Explicit end date YYYY-MM-DD (defaults to today)}
                            {--force : Re-sync even if data already exists}
                            {--min-bse=20 : Re-sync BSE if it has fewer than this many indices (upgrades Yahoo-only days)}
                            {--analytics-only : Skip fetch, only recalculate analytics for existing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync historical market indices performance from NSE/BSE';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        $exchange = strtoupper($this->argument('exchange') ?? '');

        if ($this->option('from')) {
            $startDate = Carbon::parse($this->option('from'))->startOfDay();
            $endDate   = $this->option('to')
                ? Carbon::parse($this->option('to'))->startOfDay()
                : now()->startOfDay();
            $this->info("Starting historical sync for indices (explicit range)...");
        } else {
            $months    = $this->argument('months');
            $endDate   = now()->startOfDay();
            $startDate = now()->subMonths($months)->startOfMonth();
            $this->info("Starting historical sync for indices (last {$months} months)...");
        }

        $this->info("From: {$startDate->format('d/m/Y')} To: {$endDate->format('d/m/Y')}");

        $totalDays = $startDate->diffInDays($endDate);
        $bar = $this->output->createProgressBar($totalDays + 1);
        $bar->start();

        // Start from the oldest date to ensure performance returns can be calculated sequentially
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // Skip weekends as markets are closed
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                $bar->advance();
                continue;
            }

            if (!$this->option('force') && !$this->option('analytics-only')) {
                $hasNse = IndexPrice::where('traded_date', $dateStr)->whereHas('index', fn($q) => $q->where('exchange', 'NSE'))->exists();
                $bseCount = IndexPrice::where('traded_date', $dateStr)->whereHas('index', fn($q) => $q->where('exchange', 'BSE'))->count();
                $hasBse = $bseCount >= (int) $this->option('min-bse');

                if ($exchange) {
                    $exists = ($exchange === 'NSE') ? $hasNse : $hasBse;
                    if ($exists) {
                        $currentDate->addDay();
                        $bar->advance();
                        continue;
                    }
                } elseif ($hasNse && $hasBse) {
                    $currentDate->addDay();
                    $bar->advance();
                    continue;
                }
            }

            try {
                $exitCode = Artisan::call('indices:sync', [
                    'date'             => $dateStr,
                    'exchange'         => $exchange,
                    '--analytics-only' => $this->option('analytics-only'),
                ]);

                if ($exitCode !== 0) {
                    $this->warn("\nSync failed for {$dateStr}. Moving to next date.");
                }
            } catch (\Exception $e) {
                $this->error("\nFatal error for {$dateStr}: " . $e->getMessage());
            }

            $currentDate->addDay();
            $bar->advance();

            // Periodic garbage collection for long runs
            if ($currentDate->day % 7 === 0) {
                gc_collect_cycles();
            }

            // Small sleep to avoid aggressive rate limiting from exchanges
            usleep(200000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Historical index sync completed.");
        return 0;
    }
}
