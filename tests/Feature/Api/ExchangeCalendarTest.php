<?php

namespace Tests\Feature\Api;

use App\Models\ExchangeCalendarEvent;
use App\Services\ExchangeCalendarSyncService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Throwable;

class ExchangeCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        (require database_path('migrations/2026_09_17_000000_create_exchange_calendar_events_table.php'))->up();
        (require database_path('migrations/2026_09_17_010000_add_manual_override_to_exchange_calendar_events_table.php'))->up();
    }

    public function test_sync_combines_nse_and_bse_and_persists_weekends_idempotently(): void
    {
        Http::fake([
            '*nseindia.com/api/holiday-master*' => Http::sequence()
                ->push(['CM' => [
                    ['tradingDate' => '26-Jan-2026', 'weekDay' => 'Monday', 'description' => 'Republic Day'],
                    ['tradingDate' => '08-Nov-2026', 'weekDay' => 'Sunday', 'description' => 'Diwali Laxmi Pujan'],
                    ['tradingDate' => '26-Jan-2027', 'weekDay' => 'Tuesday', 'description' => 'Republic Day'],
                ]])
                ->push(['CM' => [
                    ['tradingDate' => '08-Nov-2026', 'description' => 'Diwali Laxmi Pujan'],
                ]]),
            '*bseindia.com/*' => Http::response($this->bseHtml()),
        ]);

        $this->artisan('exchange-calendar:sync', ['--exchange' => ['NSE', 'BSE'], '--year' => [2026]])
            ->assertExitCode(0);
        $this->assertDatabaseCount('exchange_calendar_events', 105);
        $this->assertDatabaseHas('exchange_calendar_events', [
            'exchange' => 'NSE_BSE', 'event_date' => '2026-11-08', 'event_type' => 'muhurat',
        ]);
        $this->assertDatabaseHas('exchange_calendar_events', [
            'exchange' => 'NSE_BSE', 'event_date' => '2026-01-03', 'event_type' => 'weekend', 'name' => 'Saturday',
        ]);
        $this->assertDatabaseMissing('exchange_calendar_events', ['exchange' => 'NSE']);
        $this->assertDatabaseMissing('exchange_calendar_events', ['exchange' => 'BSE']);

        $this->artisan('exchange-calendar:sync', ['--exchange' => ['NSE', 'BSE'], '--year' => [2026]])
            ->assertExitCode(0);
        $this->assertDatabaseCount('exchange_calendar_events', 105);
    }

    public function test_bse_uses_the_official_annual_circular_when_configured(): void
    {
        config(['exchange_calendar.bse.supplemental_urls.2026' => []]);
        Http::fake(['*bseindia.com/*' => Http::response($this->bseHtml())]);

        app(ExchangeCalendarSyncService::class)->sync('BSE', 2026);

        Http::assertSent(fn ($request) => $request->url()
            === config('exchange_calendar.bse.year_urls.2026'));
        $this->assertDatabaseHas('exchange_calendar_events', [
            'exchange' => 'BSE',
            'event_date' => '2026-01-26',
            'source_url' => config('exchange_calendar.bse.year_urls.2026'),
        ]);
    }

    public function test_bse_merges_a_later_official_closure_notice(): void
    {
        config(['exchange_calendar.bse.supplemental_urls.2026' => [
            'https://www.bseindia.com/markets/MarketInfo/DispNewNoticesCirculars.aspx?page=update',
        ]]);
        Http::fake([
            '*page=20251212-8' => Http::response($this->bseHtml()),
            '*page=update' => Http::response('<html><body>Subject Trading Holiday on account of Municipal Corporation Elections in Maharashtra 2026 Content The Equity Segment will remain closed on January 15, 2026.</body></html>'),
        ]);

        app(ExchangeCalendarSyncService::class)->sync('BSE', 2026);

        $this->assertDatabaseHas('exchange_calendar_events', [
            'exchange' => 'BSE',
            'event_date' => '2026-01-15',
            'name' => 'Trading Holiday on account of Municipal Corporation Elections in Maharashtra 2026',
        ]);
    }

    public function test_failed_sync_preserves_existing_calendar(): void
    {
        ExchangeCalendarEvent::create($this->event(['exchange' => 'NSE', 'event_date' => '2026-01-26']));
        Http::fake(['*' => Http::response([], 503)]);

        try {
            app(ExchangeCalendarSyncService::class)->sync('NSE', 2026);
            $this->fail('Expected provider failure.');
        } catch (Throwable $exception) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('exchange_calendar_events', ['exchange' => 'NSE', 'event_date' => '2026-01-26']);
    }

    public function test_sync_preserves_manually_overridden_event(): void
    {
        ExchangeCalendarEvent::create($this->event([
            'name' => 'Admin corrected holiday',
            'source' => 'manual',
            'source_url' => 'admin://manual-override',
            'is_manually_overridden' => true,
        ]));
        Http::fake(['*' => Http::response(['CM' => [
            ['tradingDate' => '26-Jan-2026', 'description' => 'Provider name'],
        ]])]);

        app(ExchangeCalendarSyncService::class)->sync('NSE', 2026);

        $this->assertDatabaseHas('exchange_calendar_events', [
            'exchange' => 'NSE',
            'event_date' => '2026-01-26',
            'name' => 'Admin corrected holiday',
            'source' => 'manual',
            'is_manually_overridden' => true,
        ]);
    }

    public function test_lists_filtered_events_and_checks_holidays_weekends_and_muhurat(): void
    {
        ExchangeCalendarEvent::create($this->event(['exchange' => 'NSE', 'event_date' => '2026-01-26']));
        ExchangeCalendarEvent::create($this->event([
            'exchange' => 'BSE',
            'event_date' => '2026-11-08',
            'name' => 'Muhurat Trading',
            'event_type' => 'muhurat',
        ]));

        $this->getJson('/api/v1/market-calendar/holidays?exchange=nse&year=2026')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.name', 'Republic Day');

        $this->getJson('/api/v1/market-calendar/check?date=2026-01-26&exchange=nse')
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.is_exchange_holiday', true)
            ->assertJsonPath('data.is_trading_day', false);

        $this->getJson('/api/v1/market-calendar/check?date=2026-11-08&exchange=bse')
            ->assertOk()
            ->assertJsonPath('data.status', 'muhurat')
            ->assertJsonPath('data.is_weekend', true)
            ->assertJsonPath('data.is_exchange_holiday', true)
            ->assertJsonPath('data.has_special_session', true)
            ->assertJsonPath('data.is_trading_day', true);

        $this->getJson('/api/v1/market-calendar/check?date=2026-09-19&exchange=all')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'closed')
            ->assertJsonPath('data.1.status', 'closed');

        $this->getJson('/api/v1/market-calendar/check?date=2027-01-27&exchange=nse')
            ->assertOk()
            ->assertJsonPath('data.calendar_available', false)
            ->assertJsonPath('data.status', 'unknown')
            ->assertJsonPath('data.is_trading_day', null);

        $this->getJson('/api/v1/market-calendar/check?date=bad&exchange=nse')->assertStatus(422);
    }

    public function test_combined_rows_are_available_to_both_exchange_filters(): void
    {
        ExchangeCalendarEvent::create($this->event([
            'exchange' => ExchangeCalendarEvent::EXCHANGE_BOTH,
            'event_date' => '2026-01-03',
            'name' => 'Saturday',
            'event_type' => ExchangeCalendarEvent::TYPE_WEEKEND,
            'source' => 'nse_bse',
            'source_url' => 'calendar://weekend',
        ]));

        foreach (['nse', 'bse'] as $exchange) {
            $this->getJson("/api/v1/market-calendar/holidays?exchange={$exchange}&year=2026&type=weekend")
                ->assertOk()
                ->assertJsonPath('count', 1)
                ->assertJsonPath('data.0.exchanges.0', 'NSE')
                ->assertJsonPath('data.0.exchanges.1', 'BSE');

            $this->getJson("/api/v1/market-calendar/check?date=2026-01-03&exchange={$exchange}")
                ->assertOk()
                ->assertJsonPath('data.status', 'closed')
                ->assertJsonPath('data.event.type', 'weekend');
        }
    }

    private function bseHtml(): string
    {
        return <<<'HTML'
        <html><body>
        <table>
          <tr><th>SI.NO.</th><th>Holidays</th><th>Date</th><th>Day</th></tr>
          <tr><td>1</td><td>Republic Day</td><td>January 26, 2026</td><td>Monday</td></tr>
        </table>
        <p>Muhurat Trading shall be held on November 08, 2026. Timings will be notified subsequently.</p>
        </body></html>
        HTML;
    }

    private function event(array $overrides = []): array
    {
        return array_merge([
            'exchange' => 'NSE',
            'segment' => 'equity',
            'event_date' => '2026-01-26',
            'name' => 'Republic Day',
            'event_type' => 'holiday',
            'source' => 'nse',
            'source_url' => 'https://example.test/calendar',
            'source_payload' => [],
            'synced_at' => now(),
        ], $overrides);
    }
}
