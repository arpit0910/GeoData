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
            ->onSuccess(fn() => $this->logCronRun('currency:fetch-rates', true))
            ->onFailure(fn() => $this->logCronRun('currency:fetch-rates', false));

        $schedule->command('equities:sync')
            ->dailyAt('19:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->onSuccess(fn() => $this->logCronRun('equities:sync', true))
            ->onFailure(fn() => $this->logCronRun('equities:sync', false));

        $schedule->command('indices:sync')
            ->dailyAt('19:15')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->onSuccess(fn() => $this->logCronRun('indices:sync', true))
            ->onFailure(fn() => $this->logCronRun('indices:sync', false));

        $schedule->command('sync:mf-daily --force')
            ->dailyAt('21:30')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->onSuccess(fn() => $this->logCronRun('sync:mf-daily (21:30)', true))
            ->onFailure(fn() => $this->logCronRun('sync:mf-daily (21:30)', false));

        $schedule->command('sync:mf-daily --force')
            ->dailyAt('23:15')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->onSuccess(fn() => $this->logCronRun('sync:mf-daily (23:15)', true))
            ->onFailure(fn() => $this->logCronRun('sync:mf-daily (23:15)', false));

        $schedule->command('mf:sync-and-calculate')
            ->dailyAt('23:30')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->onSuccess(fn() => $this->logCronRun('mf:sync-and-calculate (23:30)', true))
            ->onFailure(fn() => $this->logCronRun('mf:sync-and-calculate (23:30)', false));

        $schedule->command('equities:sync-metadata')
            ->dailyAt('08:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(60)
            ->onSuccess(fn() => $this->logCronRun('equities:sync-metadata', true))
            ->onFailure(fn() => $this->logCronRun('equities:sync-metadata', false));

        $schedule->command('equities:sync-fundamentals')
            ->dailyAt('20:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(120)
            ->onSuccess(fn() => $this->logCronRun('equities:sync-fundamentals', true))
            ->onFailure(fn() => $this->logCronRun('equities:sync-fundamentals', false));

        $schedule->command('market:fetch-live')
            ->everyMinute()
            ->withoutOverlapping()
            ->onSuccess(fn() => $this->logCronRun('market:fetch-live', true))
            ->onFailure(fn() => $this->logCronRun('market:fetch-live', false));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function logCronRun(string $title, bool $status = true): void
    {
        CronLog::create([
            'title' => $title,
            'ip' => gethostbyname(gethostname()),
            'source' => 'scheduled',
            'status' => $status,
            'ran_at' => now('Asia/Kolkata'),
        ]);
    }
}
