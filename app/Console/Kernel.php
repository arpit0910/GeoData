<?php

namespace App\Console;

use App\Models\CronLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $ip = gethostbyname(gethostname());

        $schedule->command('currency:fetch-rates')
            ->dailyAt('20:30')
            ->after(fn() => CronLog::create(['title' => 'currency:fetch-rates', 'ip' => $ip, 'ran_at' => now()]));

        $schedule->command('equities:sync')
            ->dailyAt('19:00')->timezone('Asia/Kolkata')->withoutOverlapping()
            ->after(fn() => CronLog::create(['title' => 'equities:sync', 'ip' => $ip, 'ran_at' => now()]));

        $schedule->command('indices:sync')
            ->dailyAt('19:15')->timezone('Asia/Kolkata')->withoutOverlapping()
            ->after(fn() => CronLog::create(['title' => 'indices:sync', 'ip' => $ip, 'ran_at' => now()]));

        // MF NAVs are published 21:00–23:00 IST; run at 21:30 to catch first publish
        $schedule->command('sync:mf-daily --force')
            ->dailyAt('21:30')->timezone('Asia/Kolkata')
            ->after(fn() => CronLog::create(['title' => 'sync:mf-daily (21:30)', 'ip' => $ip, 'ran_at' => now()]));

        // Re-run at 23:15 to pick up late corrections
        $schedule->command('sync:mf-daily --force')
            ->dailyAt('23:15')->timezone('Asia/Kolkata')
            ->after(fn() => CronLog::create(['title' => 'sync:mf-daily (23:15)', 'ip' => $ip, 'ran_at' => now()]));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
