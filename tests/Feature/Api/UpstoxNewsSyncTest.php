<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxNewsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('market_news_sync:last_equity_id');
    }

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
                'series' => 'EQ',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('equities')->insert($rows);

        Http::fake(fn () => Http::response(['status' => 'success', 'data' => []]));

        $this->artisan('market:sync-upstox-news', ['--batch-size' => 30])
            ->expectsOutputToContain('Fetching news for 61 of 61 eligible stocks in batches of 30')
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

    public function test_news_sync_fetches_and_stores_every_page_returned_by_upstox(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        $isin = 'INE002A01018';
        DB::table('equities')->insert([
            'isin' => $isin,
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
            'series' => 'EQ',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake(function (Request $request) use ($isin) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page_number'] ?? 1);

            return Http::response([
                'status' => 'success',
                'data' => [
                    'NSE_EQ|'.$isin => [[
                        'heading' => "News page {$page}",
                        'summary' => "Summary from page {$page}",
                        'thumbnail' => null,
                        'article_link' => "https://provider.test/news/{$page}",
                        'published_time' => now()->subMinutes($page)->getTimestampMs(),
                    ]],
                ],
                'metadata' => ['page' => [
                    'page_number' => $page,
                    'page_size' => 100,
                    'total_records' => 3,
                    'total_pages' => 3,
                ]],
            ]);
        });

        $this->artisan('market:sync-upstox-news')
            ->expectsOutputToContain('3 fetched, 3 unique, 0 duplicate; 3 created, 0 updated, 0 unchanged; 0 batches failed')
            ->assertExitCode(0);

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['page_size'] ?? null) === '100'
                && in_array((int) ($query['page_number'] ?? 0), [1, 2, 3], true);
        });
        $this->assertDatabaseCount('market_news', 3);
        $this->assertSame(
            ['News page 1', 'News page 2', 'News page 3'],
            DB::table('market_news')->orderBy('id')->pluck('original_title')->all()
        );
    }

    public function test_news_sync_stops_remaining_batches_after_an_unauthorized_response(): void
    {
        config([
            'market_data.upstox.access_token' => 'expired-access-token',
            'market_data.upstox.client_id' => null,
            'market_data.upstox.client_secret' => null,
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        $now = now();
        foreach (range(1, 61) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
            DB::table('equities')->insert([
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => 'STOCK'.$index,
                'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
                'series' => 'EQ',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        Http::fake(['https://provider.test/*' => Http::response([
            'status' => 'error',
            'errors' => [['message' => 'Invalid token used to access API']],
        ], 401)]);

        $this->artisan('market:sync-upstox-news')
            ->expectsOutputToContain('Remaining 2 news batches were skipped')
            ->assertExitCode(1);

        Http::assertSentCount(1);
    }

    public function test_news_sync_rotates_bounded_batches_and_excludes_non_stock_series(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        foreach (range(1, 91) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
            DB::table('equities')->insert([
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => 'STOCK'.$index,
                'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
                'series' => $index === 91 ? 'N1' : 'EQ',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Http::fake(fn () => Http::response(['status' => 'success', 'data' => []]));

        $this->artisan('market:sync-upstox-news', ['--max-batches' => 2])->assertExitCode(0);

        Http::assertSentCount(2);
        $requestedKeys = collect(Http::recorded())->flatMap(function (array $recorded) {
            parse_str((string) parse_url($recorded[0]->url(), PHP_URL_QUERY), $query);
            return explode(',', $query['instrument_keys'] ?? '');
        })->filter()->all();
        $this->assertCount(60, array_unique($requestedKeys));
        $this->assertNotContains('NSE_EQ|INE000091091', $requestedKeys);

        Http::fake(fn () => Http::response(['status' => 'success', 'data' => []]));
        $this->artisan('market:sync-upstox-news', ['--max-batches' => 2])->assertExitCode(0);
        Http::assertSentCount(1);
    }

    public function test_news_sync_deduplicates_the_same_article_returned_for_multiple_instruments(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        foreach (range(1, 2) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index);
            DB::table('equities')->insert([
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => 'STOCK'.$index,
                'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
                'series' => 'EQ',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Http::fake(function () {
            $article = [[
                'heading' => 'One market story returned for two stocks',
                'summary' => 'The same article was tagged to both instruments.',
                'article_link' => 'https://upstox.com/news/market-news/one-story/article-1/',
                'published_time' => now()->getTimestampMs(),
            ]];

            return Http::response([
                'status' => 'success',
                'data' => [
                    'NSE_EQ|INE000001001' => $article,
                    'NSE_EQ|INE000002002' => $article,
                ],
                'metadata' => ['page' => [
                    'page_number' => 1,
                    'page_size' => 100,
                    'total_records' => 2,
                    'total_pages' => 1,
                ]],
            ]);
        });

        $this->artisan('market:sync-upstox-news')
            ->expectsOutputToContain('2 fetched, 1 unique, 1 duplicate; 1 created')
            ->assertExitCode(0);
        $this->assertDatabaseCount('market_news', 1);
    }
}
