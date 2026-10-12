<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DailyMarketSyncScheduleTest extends TestCase
{
    public function test_daily_price_jobs_always_include_return_enrichment(): void
    {
        Artisan::call('schedule:list', ['--timezone' => 'Asia/Kolkata']);
        $schedule = Artisan::output();

        $this->assertStringContainsString('equities:sync --force', $schedule);
        $this->assertSame(2, substr_count($schedule, 'mf:sync-and-calculate'));
        $this->assertStringNotContainsString('sync:mf-daily --force --skip-returns', $schedule);
        $this->assertStringContainsString('market:sync-upstox-news --batch-size=30 --all', $schedule);
        $this->assertStringContainsString('market:rewrite-news --limit=20 --retry', $schedule);
    }
}
