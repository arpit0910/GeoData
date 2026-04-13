<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EquitySyncHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equities:sync-history {months=12 : Number of months to sync back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync historical equity data for the last X months';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $months = $this->argument('months');
        $startDate = now()->subMonths($months)->startOfDay();
        $endDate = now()->startOfDay();

        $this->info("Starting historical sync for the last {$months} months...");
        $this->info("From: {$startDate->format('Y-m-d')} To: {$endDate->format('Y-m-d')}");

        $currentDate = clone $startDate;
        $totalDays = $startDate->diffInDays($endDate);
        $bar = $this->output->createProgressBar($totalDays + 1);
        $bar->start();

        while ($currentDate <= $endDate) {
            // Skip weekends
            if (!$currentDate->isWeekend()) {
                $dateString = $currentDate->format('Y-m-d');
                
                try {
                    $this->callSilent('equities:sync', [
                        'date' => $dateString
                    ]);
                } catch (\Exception $e) {
                    $this->error("\nFailed to sync for {$dateString}: " . $e->getMessage());
                }
            }
            
            $currentDate->addDay();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Historical sync completed.");

        return Command::SUCCESS;
    }
}
