<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\IndexService;
use Tests\TestCase;

class UpstoxIndexQuoteSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_and_stores_live_index_quotes(): void
    {
        config([
            'cache.default' => 'array',
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.ltp_url' => 'https://api.upstox.test/v3/market-quote/ltp',
        ]);
        DB::table('indices')->insert([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 50',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Http::fake(['*' => Http::response([
            'status' => 'success',
            'data' => [
                'NSE_INDEX:Nifty 50' => [
                    'last_price' => 25125.50,
                    'cp' => 25000,
                    'volume' => 1234,
                ],
            ],
        ])]);

        $this->artisan('market:sync-upstox-index-quotes', ['--snapshot-eod' => true])
            ->expectsOutputToContain('Requested: 1; saved: 1; missing/failed: 0')
            ->expectsOutputToContain('EOD index price rows saved: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('index_quotes', [
            'index_code' => 'NIFTY_50',
            'price' => 25125.5,
            'previous_close' => 25000,
            'change_percent' => 0.502,
        ]);
        $snapshot = app(IndexService::class)->getSnapshot()->first();
        $this->assertSame('live', $snapshot['source']);
        $this->assertSame(25125.5, $snapshot['close']);
        $this->assertDatabaseHas('indices_prices', [
            'index_code' => 'NIFTY_50',
            'traded_date' => now('Asia/Kolkata')->toDateString(),
            'close' => 25125.5,
        ]);

        // Re-running the snapshot updates the same index/date row.
        $this->artisan('market:sync-upstox-index-quotes', ['--snapshot-eod' => true])->assertExitCode(0);
        $this->assertSame(1, DB::table('indices_prices')->count());
        $this->assertSame(1, DB::table('index_quotes')->count());
    }

    public function test_command_auto_bootstraps_missing_index_instrument_keys(): void
    {
        config([
            'cache.default' => 'array',
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.ltp_url' => 'https://api.upstox.test/v3/market-quote/ltp',
        ]);

        // Start with empty indices table
        $this->assertSame(0, DB::table('indices')->count());

        Http::fake(['*' => Http::response([
            'status' => 'success',
            'data' => [
                'NSE_INDEX:Nifty 50' => [
                    'last_price' => 25100.0,
                    'cp' => 25000.0,
                    'volume' => 100,
                ],
            ],
        ])]);

        // Command auto-bootstraps keys instead of failing with missing keys error
        $this->artisan('market:sync-upstox-index-quotes')
            ->expectsOutputToContain('Initializing index instrument keys...')
            ->assertExitCode(0);

        $this->assertGreaterThan(0, DB::table('indices')->whereNotNull('upstox_instrument_key')->count());
        $this->assertDatabaseHas('indices', [
            'index_code' => 'NIFTY_50',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 50',
        ]);
        $this->assertDatabaseHas('index_quotes', [
            'index_code' => 'NIFTY_50',
            'price' => 25100.0,
        ]);
    }

    public function test_invalid_index_key_does_not_block_valid_quotes_in_the_same_batch(): void
    {
        config([
            'cache.default' => 'array',
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.ltp_url' => 'https://api.upstox.test/v3/market-quote/ltp',
        ]);

        foreach ([
            ['NIFTY_50', 'Nifty 50', 'NSE_INDEX|Nifty 50'],
            ['STALE_INDEX', 'Stale Index', 'NSE_INDEX|Stale Index'],
            ['SENSEX', 'Sensex', 'BSE_INDEX|SENSEX'],
        ] as [$code, $name, $key]) {
            DB::table('indices')->insert([
                'index_code' => $code,
                'index_name' => $name,
                'exchange' => str_starts_with($key, 'BSE_') ? 'BSE' : 'NSE',
                'upstox_instrument_key' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Http::fake(function ($request) {
            $keys = explode(',', (string) $request['instrument_key']);

            if (in_array('NSE_INDEX|Stale Index', $keys, true)) {
                return Http::response([
                    'status' => 'error',
                    'errors' => [['message' => 'One of either symbol or instrument_key is invalid.']],
                ], 400);
            }

            $data = [];
            foreach ($keys as $key) {
                $data[str_replace('|', ':', $key)] = [
                    'last_price' => $key === 'BSE_INDEX|SENSEX' ? 81000 : 25125.5,
                    'cp' => $key === 'BSE_INDEX|SENSEX' ? 80500 : 25000,
                ];
            }

            return Http::response(['status' => 'success', 'data' => $data]);
        });

        $this->artisan('market:sync-upstox-index-quotes')
            ->expectsOutputToContain('Skipped invalid Upstox key for STALE_INDEX')
            ->expectsOutputToContain('Requested: 3; saved: 2; missing/failed: 1; invalid keys skipped: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('index_quotes', ['index_code' => 'NIFTY_50', 'price' => 25125.5]);
        $this->assertDatabaseHas('index_quotes', ['index_code' => 'SENSEX', 'price' => 81000]);
        $this->assertDatabaseMissing('index_quotes', ['index_code' => 'STALE_INDEX']);
    }
}
