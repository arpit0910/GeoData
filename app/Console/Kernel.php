<?php

namespace App\Console;

use App\Models\CronLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('currency:fetch-rates')
            ->dailyAt('20:30')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->after(fn() => $this->logCronRun('currency:fetch-rates'));

        $schedule->command('equities:sync')
            ->dailyAt('19:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->after(fn() => $this->logCronRun('equities:sync'));

        $schedule->command('indices:sync')
            ->dailyAt('19:15')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->after(fn() => $this->logCronRun('indices:sync'));

        $schedule->command('sync:mf-daily --force')
            ->dailyAt('21:30')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->after(fn() => $this->logCronRun('sync:mf-daily (21:30)'));

        $schedule->command('sync:mf-daily --force')
            ->dailyAt('23:15')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->after(fn() => $this->logCronRun('sync:mf-daily (23:15)'));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function logCronRun(string $title): void
    {
        CronLog::create([
            'title' => $title,
            'ip' => gethostbyname(gethostname()),
            'ran_at' => now('Asia/Kolkata'),
        ]);
    }
}
