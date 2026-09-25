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

        $this->markScheduled($schedule->command('market:sync-global-instruments')
            ->dailyAt('06:30')->timezone('Asia/Kolkata')->withoutOverlapping(30));

        $this->markScheduled($schedule->command('market:sync-company-fundamentals --limit=25 --delay=250')
            ->dailyAt('02:00')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        // 1. Equities / Stocks: Constantly synced every minute during active trading hours (09:15 to 15:30 IST)
        $this->markScheduled($schedule->command('market:sync-upstox-quotes --type=stocks --mode=ltp')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

        // 2. Bonds, Debentures & Fixed Income: Synced once daily after market closing (18:00 IST)
        $this->markScheduled($schedule->command('market:sync-upstox-quotes --type=bonds --mode=ltp --once-daily')
            ->dailyAt('18:00')
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->runInBackground()
            ->withoutOverlapping(120));

        $this->markScheduled($schedule->command('market:sync-upstox-news --batch-size=30 --limit=60')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

        $this->markScheduled($schedule->command('market:sync-upstox-events --limit=25')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

        $this->markScheduled($schedule->command('market:sync-upstox-splits --limit=25')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

        $this->markScheduled($schedule->command('market:sync-upstox-bonuses --limit=25')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

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
