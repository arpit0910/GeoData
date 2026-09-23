<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $this->markScheduled($schedule->command('currency:fetch-rates')
            ->dailyAt('20:30')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('equities:sync')
            ->dailyAt('19:00')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('indices:sync')
            ->dailyAt('19:15')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('sync:mf-daily --force')
            ->dailyAt('21:30')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('sync:mf-daily --force')
            ->dailyAt('23:15')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('mf:sync-and-calculate')
            ->dailyAt('23:30')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('equities:sync-metadata')
            ->dailyAt('08:00')->timezone('Asia/Kolkata')->withoutOverlapping(60));

        $this->markScheduled($schedule->command('equities:sync-fundamentals')
            ->dailyAt('20:00')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('market:sync-upstox-quotes --batch-size=500')
            ->everyFiveMinutes()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '16:00')
            ->runInBackground()
            ->withoutOverlapping(10));

        $this->markScheduled($schedule->command('exchange-calendar:sync')
            ->dailyAt('06:00')->timezone('Asia/Kolkata')->withoutOverlapping(60));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function markScheduled(Event $event): void
    {
        $event
            ->before(fn () => putenv('SETUGEO_CRON_SOURCE=scheduled'))
            ->after(fn () => putenv('SETUGEO_CRON_SOURCE'));
    }
}
