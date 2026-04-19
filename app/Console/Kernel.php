<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('currency:fetch-rates')->dailyAt('20:30');
        $schedule->command('equities:sync')->dailyAt('19:00')->timezone('Asia/Kolkata');
        $schedule->command('indices:sync')->dailyAt('19:15')->timezone('Asia/Kolkata');
        // MF NAVs are published 21:00–23:00 IST; run at 21:30 to catch first publish
        $schedule->command('sync:mf-daily --force')->dailyAt('21:30')->timezone('Asia/Kolkata');
        // Re-run at 23:15 to pick up late corrections
        $schedule->command('sync:mf-daily --force')->dailyAt('23:15')->timezone('Asia/Kolkata');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
