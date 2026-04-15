<?php

namespace App\Console\Commands;

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
    protected $signature = 'indices:sync-history {months=12 : Number of months to sync back}';

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
        $months = $this->argument('months');
        $endDate = now()->startOfDay();
        $startDate = now()->subMonths($months)->startOfMonth();

        $this->info("Starting historical sync for indices (last {$months} months)...");
        $this->info("From: {$startDate->format('d/m/Y')} To: {$endDate->format('d/m/Y')}");
        
        // Start from the oldest date to ensure performance returns can be calculated sequentially
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Skip weekends as markets are closed
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            $this->info("\n--- Processing: {$currentDate->format('d/m/Y')} ---");
            
            try {
                $exitCode = Artisan::call('indices:sync', [
                    'date' => $dateStr
                ]);
                $this->line(Artisan::output());
                
                if ($exitCode !== 0) {
                    $this->warn("Sync failed for {$dateStr}. Moving to next date.");
                }
            } catch (\Exception $e) {
                $this->error("Fatal error for {$dateStr}: " . $e->getMessage());
            }

            $currentDate->addDay();
            // Sleep briefly to avoid exchange rate limiting
            usleep(500000); 
        }

        $this->info("\nHistorical index sync completed.");
        return 0;
    }
}
