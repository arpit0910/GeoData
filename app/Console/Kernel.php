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

        // The 18:00 quote snapshot already creates today's rows. Force the
        // bhavcopy pass to enrich those rows with full details and returns.
        $this->markScheduled($schedule->command('equities:sync --force')
            ->dailyAt('19:00')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('indices:sync --force')
            ->dailyAt('19:15')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('mf:sync-and-calculate')
            ->dailyAt('21:30')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        // Independent morning recovery from Upstox's daily MF instrument file.
        $this->markScheduled($schedule->command('mf:sync-upstox-daily')
            ->dailyAt('06:10')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        // Repair any dates missed in the preceding two weeks. The command is
        // idempotent, so existing ISIN/date rows are updated without duplicates.
        $this->markScheduled($schedule->command('mf:backfill', [
            '--from' => now('Asia/Kolkata')->subDays(14)->toDateString(),
            '--to' => now('Asia/Kolkata')->subDay()->toDateString(),
        ])->dailyAt('06:45')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('mf:sync-and-calculate')
            ->dailyAt('23:30')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('equities:sync-metadata')
            ->dailyAt('08:00')->timezone('Asia/Kolkata')->withoutOverlapping(60));

        $this->markScheduled($schedule->command('equities:sync-fundamentals')
            ->dailyAt('20:00')->timezone('Asia/Kolkata')->withoutOverlapping(120));

        $this->markScheduled($schedule->command('market:sync-global-instruments')
            ->dailyAt('06:30')->timezone('Asia/Kolkata')->withoutOverlapping(30));

        // Upstox refreshes its instrument masters around 06:00 IST. This also
        // fills newly listed stocks and all provider metadata available for them.
        $this->markScheduled($schedule->command('equities:sync-upstox-instruments')
            ->dailyAt('06:15')->timezone('Asia/Kolkata')->withoutOverlapping(60));

        $this->markScheduled($schedule->command('market:sync-company-fundamentals --limit=25 --delay=250')
            ->dailyAt('02:00')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        $this->markScheduled($schedule->command('market:sync-company-fundamentals --dataset=profile --missing-profile --limit=250 --delay=200')
            ->dailyAt('02:30')->timezone('Asia/Kolkata')->withoutOverlapping(180));

        // Refresh previously validated indices first so unsupported index keys
        // cannot delay the one-minute live quote path.
        $this->markScheduled($schedule->command('market:sync-upstox-index-quotes --quoted-only')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2), 'market:sync-upstox-index-quotes (Live)');

        // 1. Equities / Stocks: Constantly synced every minute during active trading hours (09:15 to 15:30 IST)
        $this->markScheduled($schedule->command('market:sync-upstox-quotes --type=stocks --mode=ltp')
            ->everyMinute()
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(2));

        $this->markScheduled($schedule->command('market:sync-upstox-index-quotes --mode=full --snapshot-eod')
            ->dailyAt('18:05')
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(120), 'market:sync-upstox-index-quotes (EOD)');

        // Capture the final full quote as the day's EOD history. The snapshot
        // is idempotent on ISIN + date, so retries update instead of duplicate.
        $this->markScheduled($schedule->command('market:sync-upstox-quotes --type=stocks --mode=full --snapshot-eod')
            ->dailyAt('18:00')
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(120));

        $this->markScheduled($schedule->command('stock-nudges:generate market')
            ->cron(config('stock_nudges.market_schedule'))
            ->timezone('Asia/Kolkata')
            ->weekdays()
            ->between('09:15', '15:30')
            ->runInBackground()
            ->withoutOverlapping(10));

        $this->markScheduled($schedule->command('stock-nudges:generate eod')
            ->cron(config('stock_nudges.eod_schedule'))
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(60));

        // 2. Bonds, Debentures & Fixed Income: Synced once daily after market closing.
        $this->markScheduled($schedule->command('market:sync-upstox-quotes --type=bonds --mode=ltp --once-daily')
            ->dailyAt('18:10')
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(120));

        $this->markScheduled($schedule->command('market:sync-upstox-news --batch-size=30 --max-batches=20')
            ->hourly()
            ->timezone('Asia/Kolkata')
            ->between('07:00', '23:00')
            ->runInBackground()
            ->withoutOverlapping(120), 'market:sync-upstox-news');

        $this->markScheduled($schedule->command('market:sync-upstox-news --batch-size=30 --all')
            ->cron('30 6,18 * * *')
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(360), 'market:sync-upstox-news --all');

        // Upstox exposes every corporate-action type through a per-ISIN endpoint.
        // A bounded hourly rotation avoids provider throttling while eventually
        // covering every active equity and prioritising never-checked companies.
        $this->markScheduled($schedule->command('market:sync-upstox-events --limit=500 --delay=200')
            ->hourly()
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(120));

        $this->markScheduled($schedule->command('market:rewrite-news --limit=20 --retry')
            ->cron('5,35 * * * *')
            ->timezone('Asia/Kolkata')
            ->runInBackground()
            ->withoutOverlapping(30));

        $this->markScheduled($schedule->command('exchange-calendar:sync')
            ->dailyAt('06:00')->timezone('Asia/Kolkata')->withoutOverlapping(60));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function markScheduled(Event $event, ?string $title = null): void
    {
        $event
            ->before(function () use ($title) {
                putenv('SETUGEO_CRON_SOURCE=scheduled');
                if ($title) {
                    putenv("SETUGEO_CRON_TITLE={$title}");
                }
            })
            ->after(function () {
                putenv('SETUGEO_CRON_SOURCE');
                putenv('SETUGEO_CRON_TITLE');
            });
    }
}
