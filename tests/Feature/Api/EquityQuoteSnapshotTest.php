<?php

namespace Tests\Feature\Api;

use App\Models\Equity;
use App\Services\EquityQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquityQuoteSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_quotes_keep_nse_and_bse_values_in_one_record_per_isin(): void
    {
        $service = app(EquityQuoteService::class);
        $quotedAt = now()->utc()->toIso8601String();

        $service->store('INE002A01018', 'NSE', $this->quote(100, $quotedAt, 'RELIANCE'));
        $service->store('INE002A01018', 'NSE', $this->quote(101, now()->utc()->addMinute()->toIso8601String(), 'RELIANCE'));
        $service->store('INE002A01018', 'BSE', $this->quote(102, now()->utc()->addMinute()->toIso8601String(), '500325'));
        $service->store('INE002A01018', 'BSE', $this->quote(90, now()->utc()->subMinute()->toIso8601String(), '500325'));

        $this->assertSame(1, DB::table('equity_quotes')->where('isin', 'INE002A01018')->count());
        $row = DB::table('equity_quotes')->where('isin', 'INE002A01018')->first();
        $this->assertSame(101.0, (float) $row->nse_price);
        $this->assertSame(102.0, (float) $row->bse_price);
        $this->assertSame('RELIANCE', $row->nse_symbol);
        $this->assertSame('500325', $row->bse_symbol);
        $this->assertSame('NSE', $row->exchange);
        $this->assertCount(2, $service->latest('INE002A01018'));
    }

    public function test_eod_snapshot_upserts_one_equity_price_per_isin_and_date(): void
    {
        $this->travelTo(now('Asia/Kolkata')->setTime(18, 0));

        Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'series' => 'EQ',
            'is_active' => true,
        ]);

        $service = app(EquityQuoteService::class);
        $service->store(
            'INE002A01018',
            'NSE',
            $this->quote(125.50, now()->utc()->toIso8601String(), 'RELIANCE', [
                'open' => 124.50,
                'high' => 126.00,
                'low' => 123.75,
                'close' => 124.00,
                'volume' => 10000,
            ])
        );

        $this->assertDatabaseHas('equity_quotes', [
            'isin' => 'INE002A01018',
            'previous_close' => 124.0000,
            'change_percent' => null,
            'live_volume' => 10000,
        ]);
        $service->store(
            'INE002A01018',
            'BSE',
            $this->quote(126.10, now()->utc()->toIso8601String(), '500325', [
                'open' => 124.40,
                'high' => 126.50,
                'low' => 123.60,
                'close' => 123.90,
                'volume' => 8000,
            ])
        );

        $this->assertSame(1, $service->snapshotEndOfDay());

        $service->store(
            'INE002A01018',
            'NSE',
            $this->quote(126.25, now()->utc()->addMinute()->toIso8601String(), 'RELIANCE', [
                'open' => 124.50,
                'high' => 127.00,
                'low' => 123.75,
                'close' => 124.00,
                'volume' => 12000,
            ])
        );

        $this->assertSame(1, $service->snapshotEndOfDay());
        $this->assertSame(1, DB::table('equity_prices')->where('isin', 'INE002A01018')->count());
        $row = DB::table('equity_prices')->where('isin', 'INE002A01018')->first();
        $this->assertSame(now('Asia/Kolkata')->toDateString(), substr((string) $row->traded_date, 0, 10));
        $this->assertSame(126.25, (float) $row->nse_close);
        $this->assertSame(127.0, (float) $row->nse_high);
        $this->assertSame(12000, (int) $row->nse_volume);
        $this->assertSame(126.10, (float) $row->bse_close);
        $this->assertSame(126.50, (float) $row->bse_high);
        $this->assertSame(8000, (int) $row->bse_volume);
    }

    public function test_provider_epoch_placeholder_uses_the_fetch_time(): void
    {
        $fetchedAt = '2026-09-17T05:01:22+00:00';
        $quote = $this->quote(1000000, '1970-01-01T00:00:00+00:00', '975UPPCL25.BO');
        $quote['fetched_at'] = $fetchedAt;

        app(EquityQuoteService::class)->store('INE540P07244', 'BSE', $quote);

        $this->assertDatabaseHas('equity_quotes', [
            'isin' => 'INE540P07244',
            'bse_quoted_at' => '2026-09-17 05:01:22',
            'bse_fetched_at' => '2026-09-17 05:01:22',
        ]);
    }

    private function quote(float $price, string $quotedAt, string $symbol, array $ohlc = []): array
    {
        return [
            'symbol' => $symbol,
            'price' => $price,
            'previous_close' => 124.00,
            'quoted_at' => $quotedAt,
            'fetched_at' => $quotedAt,
            'source' => 'live',
            'provider' => 'test',
            'exchange' => 'NSE',
            'market_data' => [
                'ohlc' => $ohlc,
                'volume' => $ohlc['volume'] ?? null,
                'average_price' => 125.10,
            ],
        ];
    }
}
