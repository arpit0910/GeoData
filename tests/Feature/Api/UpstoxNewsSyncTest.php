<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxNewsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_sync_processes_every_eligible_instrument_in_thirty_item_batches(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);

        $now = now();
        $rows = [];
        foreach (range(1, 61) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
            $rows[] = [
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => $index < 61 ? 'STOCK'.$index : null,
                'bse_symbol' => $index === 61 ? 'BSESTOCK61' : null,
                'upstox_nse_instrument_key' => $index < 61 ? 'NSE_EQ|'.$isin : null,
                'upstox_bse_instrument_key' => $index === 61 ? 'BSE_EQ|'.$isin : null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('equities')->insert($rows);

        Http::fake(fn () => Http::response(['status' => 'success', 'data' => []]));

        $this->artisan('market:sync-upstox-news', ['--batch-size' => 30])
            ->expectsOutputToContain('Fetching news for up to 61 instruments in batches of 30')
            ->assertExitCode(0);

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $keys = explode(',', $query['instrument_keys'] ?? '');
            return count($keys) >= 1 && count($keys) <= 30;
        });
        $requestedKeys = collect(Http::recorded())->flatMap(function (array $recorded) {
            parse_str((string) parse_url($recorded[0]->url(), PHP_URL_QUERY), $query);
            return explode(',', $query['instrument_keys'] ?? '');
        })->filter()->values()->all();

        $this->assertCount(61, array_unique($requestedKeys));
        $this->assertContains('BSE_EQ|INE000061061', $requestedKeys);
    }
}
