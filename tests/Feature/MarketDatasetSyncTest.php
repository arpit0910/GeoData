<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Services\GlobalInstrumentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketDatasetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_instrument_master_is_synchronized_and_missing_rows_are_deactivated(): void
    {
        config(['market_data.upstox.global_instruments_url' => 'https://provider.test/global.json.gz']);

        Http::fakeSequence('https://provider.test/global.json.gz')
            ->push(gzencode(json_encode([
                [
                    'segment' => 'GLOBAL_INDEX',
                    'name' => 'Global Index One',
                    'exchange' => 'GLOBAL',
                    'country' => 'India',
                    'latency' => '120 Seconds',
                    'instrument_key' => 'GLOBAL_INDEX|ONE',
                    'trading_symbol' => 'ONE',
                    'start_time' => '6.30AM Monday',
                    'end_time' => '2.45AM Saturday',
                    'week_days' => 'Mon-Fri',
                ],
                [
                    'segment' => 'GLOBAL_INDICATOR',
                    'name' => 'Indicator Two',
                    'exchange' => 'GLOBAL',
                    'latency' => '20 Seconds',
                    'instrument_key' => 'GLOBAL_INDICATOR|TWO',
                    'trading_symbol' => 'TWO',
                ],
            ], JSON_THROW_ON_ERROR)))
            ->push(gzencode(json_encode([[
                'segment' => 'GLOBAL_INDEX',
                'name' => 'Global Index One',
                'exchange' => 'GLOBAL',
                'instrument_key' => 'GLOBAL_INDEX|ONE',
                'trading_symbol' => 'ONE',
            ]], JSON_THROW_ON_ERROR)));

        $stats = app(GlobalInstrumentSyncService::class)->sync();

        $this->assertSame(2, $stats['saved']);
        $this->assertDatabaseHas('global_instruments', [
            'instrument_key' => 'GLOBAL_INDEX|ONE',
            'segment' => 'GLOBAL_INDEX',
            'is_active' => true,
        ]);
        $this->assertStringNotContainsString(
            'GLOBAL_INDEX|ONE',
            GlobalInstrument::where('name', 'Global Index One')->firstOrFail()->toJson()
        );

        $stats = app(GlobalInstrumentSyncService::class)->sync();

        $this->assertSame(1, $stats['deactivated']);
        $this->assertDatabaseHas('global_instruments', [
            'instrument_key' => 'GLOBAL_INDICATOR|TWO',
            'is_active' => false,
        ]);
    }

    public function test_complete_company_fundamentals_are_synchronized_for_an_isin(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-token',
            'market_data.upstox.fundamentals_url' => 'https://provider.test/v2/fundamentals',
        ]);

        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Example Company',
            'nse_symbol' => 'EXAMPLE',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        $equity->forceFill(['upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018'])->save();

        Http::fake(function (Request $request) {
            return Http::response([
                'status' => 'success',
                'data' => ['request_url' => $request->url()],
            ]);
        });

        $this->artisan('market:sync-company-fundamentals', [
            '--isin' => [$equity->isin],
            '--delay' => 0,
        ])->assertExitCode(0);

        $this->assertSame(13, DB::table('company_fundamentals')->where('isin', $equity->isin)->count());
        $this->assertDatabaseHas('company_fundamentals', [
            'isin' => $equity->isin,
            'dataset' => 'income_statement',
            'statement_type' => 'standalone',
            'time_period' => 'quarterly',
        ]);
        $this->assertDatabaseHas('company_fundamentals', [
            'isin' => $equity->isin,
            'dataset' => 'corporate_actions',
        ]);
        Http::assertSentCount(13);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-token'));
    }
}
