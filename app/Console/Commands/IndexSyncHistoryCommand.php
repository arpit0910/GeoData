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
    protected $signature = 'indices:sync-history {months=12 : Number of months to sync back} {exchange? : Optional exchange (NSE or BSE)}';

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
        $months = $this->argument('months');
        $exchange = strtoupper($this->argument('exchange'));
        $endDate = now()->startOfDay();
        $startDate = now()->subMonths($months)->startOfMonth();

        $this->info("Starting historical sync for indices (last {$months} months)...");
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

            // Check if both NSE and BSE records exist to allow filling gaps in history
            $hasNse = IndexPrice::where('traded_date', $dateStr)->whereHas('index', fn($q) => $q->where('exchange', 'NSE'))->exists();
            $hasBse = IndexPrice::where('traded_date', $dateStr)->whereHas('index', fn($q) => $q->where('exchange', 'BSE'))->exists();

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

            try {
                // Call the sync command for the specific date
                $exitCode = Artisan::call('indices:sync', [
                    'date' => $dateStr,
                    'exchange' => $exchange
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
