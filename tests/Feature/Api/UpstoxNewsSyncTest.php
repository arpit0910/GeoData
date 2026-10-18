<?php

namespace Tests\Feature\Api;

use App\Models\MarketNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpstoxNewsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('market_news_sync:last_equity_id');
        Cache::forget('market_news_sync:retry_equity_ids');
    }

    public function test_news_sync_fails_before_querying_instruments_without_a_token(): void
    {
        config([
            'market_data.upstox.analytics_token' => null,
            'market_data.upstox.access_token' => null,
            'market_data.upstox.client_id' => null,
            'market_data.upstox.client_secret' => null,
        ]);
        Http::fake();

        $this->artisan('market:sync-upstox-news')
            ->expectsOutputToContain('No active Upstox token is available')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_news_sync_stores_thumbnail_locally_and_api_returns_its_accessible_url(): void
    {
        Storage::fake('public');
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
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        Http::fake(function (Request $request) use ($isin, $image) {
            if ($request->url() === 'https://images.provider.test/story.png') {
                return Http::response($image, 200, ['Content-Type' => 'image/png']);
            }

            return Http::response([
                'status' => 'success',
                'data' => ['NSE_EQ|'.$isin => [[
                    'heading' => 'Reliance market update',
                    'summary' => 'A verified company update.',
                    'thumbnail' => 'https://images.provider.test/story.png',
                    'article_link' => 'https://provider.test/news/reliance-update',
                    'published_time' => now()->getTimestampMs(),
                ]]],
                'metadata' => ['page' => [
                    'page_number' => 1,
                    'page_size' => 100,
                    'total_records' => 1,
                    'total_pages' => 1,
                ]],
            ]);
        });

        $this->artisan('market:sync-upstox-news')->assertExitCode(0);

        $news = MarketNews::firstOrFail();
        $path = $news->getRawOriginal('thumbnail');
        $this->assertStringStartsWith('news-thumbnails/', $path);
        Storage::disk('public')->assertExists($path);

        $news->forceFill([
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
        ])->save();
        $thumbnailUrl = route('market.news.thumbnail', $news);

        $this->getJson('/api/v1/market/news')
            ->assertOk()
            ->assertJsonPath('data.0.thumbnail', $thumbnailUrl)
            ->assertJsonPath('data.0.article_url', route('market.news.show', $news));
        $this->get($thumbnailUrl)
            ->assertOk()
            ->assertHeader('Cache-Control', 'immutable, max-age=2592000, public');
        $this->get(route('market.news'))
            ->assertOk()
            ->assertSee($thumbnailUrl, false)
            ->assertDontSee('images.provider.test', false);
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

        $this->artisan('market:sync-upstox-news', ['--batch-size' => 30, '--max-batches' => 1, '--all' => true])
            ->expectsOutputToContain('Fetching news for 61 of 61 eligible equity and index instruments in batches of 30')
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

    public function test_news_sync_fetches_and_stores_index_news(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        DB::table('indices')->insert([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 50',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake(fn () => Http::response([
            'status' => 'success',
            'data' => [
                'NSE_INDEX|Nifty 50' => [[
                    'heading' => 'Nifty closes higher after broad market rally',
                    'summary' => 'The benchmark index ended the session higher.',
                    'article_link' => 'https://provider.test/news/nifty-rally',
                    'published_time' => now()->getTimestampMs(),
                ]],
            ],
            'metadata' => ['page' => [
                'page_number' => 1,
                'page_size' => 100,
                'total_records' => 1,
                'total_pages' => 1,
            ]],
        ]));

        $this->artisan('market:sync-upstox-news')->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'NSE_INDEX%7CNifty%2050'));
        $this->assertDatabaseHas('market_news', [
            'isin' => null,
            'symbol' => 'NIFTY_50',
            'instrument_key' => 'NSE_INDEX|Nifty 50',
            'original_title' => 'Nifty closes higher after broad market rally',
        ]);
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

    public function test_news_sync_continues_when_live_pagination_totals_change(): void
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
                'data' => ['NSE_EQ|'.$isin => [[
                    'heading' => "Changing feed page {$page}",
                    'summary' => "Summary {$page}",
                    'article_link' => "https://provider.test/news/changing-{$page}",
                    'published_time' => now()->getTimestampMs(),
                ]]],
                'metadata' => ['page' => [
                    'page_number' => $page,
                    'page_size' => 100,
                    'total_records' => $page === 1 ? 2 : 3,
                    'total_pages' => 2,
                ]],
            ]);
        });

        $this->artisan('market:sync-upstox-news', ['--delay' => 0])
            ->assertExitCode(0);

        $this->assertDatabaseCount('market_news', 2);
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

    public function test_news_sync_rotates_bounded_batches_across_every_active_instrument_series(): void
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
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return in_array('NSE_EQ|INE000091091', explode(',', $query['instrument_keys'] ?? ''), true);
        });
    }

    public function test_news_sync_retries_a_transiently_failed_batch_on_the_next_run(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.news_url' => 'https://provider.test/v2/news',
        ]);
        foreach (range(1, 31) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
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
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return $attempts <= 3
                ? Http::response(['status' => 'error'], 503)
                : Http::response(['status' => 'success', 'data' => []]);
        });

        $this->artisan('market:sync-upstox-news', ['--max-batches' => 2])
            ->expectsOutputToContain('This batch will be retried on the next run')
            ->assertExitCode(1);
        $this->assertCount(30, Cache::get('market_news_sync:retry_equity_ids'));

        $this->artisan('market:sync-upstox-news', ['--max-batches' => 2])
            ->assertExitCode(0);

        Http::assertSentCount(5);
        $this->assertSame([], Cache::get('market_news_sync:retry_equity_ids', []));
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
                    // Provider totals may count the unique story once even when
                    // it is mapped to more than one requested instrument.
                    'total_records' => 1,
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
