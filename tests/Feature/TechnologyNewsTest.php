<?php

namespace Tests\Feature;

use App\Models\{NewsArticle, NewsCategory, NewsSource, NewsSyncLog, User};
use App\Services\TechnologyNews\{Normalizer, Providers, SafeHttp, Synchronizer};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Http, Queue};
use Tests\TestCase;

class TechnologyNewsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        $this->artisan('news:install')->assertExitCode(0);
        NewsSource::query()->update(['is_active' => false]);
        $this->app->bind(SafeHttp::class, fn () => new class extends SafeHttp {
            protected function addresses(string $host): array { return ['93.184.216.34']; }
        });
        Http::preventStrayRequests();
    }
    private function source(string $type = 'dev'): NewsSource
    {
        return NewsSource::create(['name' => 'Test provider', 'source_type' => $type, 'base_url' => 'https://example.com',
            'api_endpoint' => 'https://example.com/api', 'feed_url' => 'https://example.com/feed', 'is_active' => true,
            'display_mode' => 'excerpt', 'permission_reference' => 'https://example.com/terms', 'permission_notes' => 'Test licence', 'permissions_verified_at' => now('UTC'), 'allow_ai_rewrite' => true]);
    }
    private function row(int $id = 1): array
    {
        return ['id' => $id, 'title' => 'Python developer tools '.$id, 'canonical_url' => 'https://publisher.example/story/'.$id,
            'published_at' => now('UTC')->subHour()->toIso8601String(), 'description' => '<script>bad()</script>Real &amp; useful',
            'user' => ['name' => 'Original Author'], 'tag_list' => ['python'], 'cover_image' => 'https://example.com/unlicensed.jpg'];
    }
    private function sync(NewsSource $source): string { return app(Synchronizer::class)->sync($source->fresh(), true); }
    private function publishFixtures(): void
    {
        foreach (NewsArticle::all() as $article) {
            $article->update(['ai_title' => $article->title, 'ai_summary' => $article->description ?: $article->title,
                'editorial_status' => NewsArticle::STATUS_PUBLISHED, 'rewrite_source_hash' => $article->content_hash, 'rewritten_at' => now('UTC')]);
        }
        \App\Services\TechnologyNews\NewsCache::invalidate();
    }
    private function fake($responses): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake($responses);
    }
    public function test_api_sync_sanitizes_and_categorizes_without_importing_images(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([$this->row()])]);
        $this->assertSame('success', $this->sync($s)); $a = NewsArticle::firstOrFail();
        $this->assertSame('Real & useful', $a->description); $this->assertNull($a->image_url); $this->assertNull($a->content);
        $this->assertSame('Original Author', $a->author_name); $this->assertSame('programming-development', $a->category->slug);
        $this->assertSame('UTC', $a->published_at->timezoneName);
    }
    public function test_rss_and_atom_parse_dates_authors_and_links(): void
    {
        foreach (['<rss><channel><item><guid>one</guid><title>Cybersecurity report</title><link>https://publisher.example/rss</link><pubDate>Mon, 16 Nov 2026 10:00:00 GMT</pubDate><description>Source excerpt</description></item></channel></rss>',
            '<feed xmlns="http://www.w3.org/2005/Atom"><entry><id>two</id><title>Robotics report</title><link href="https://publisher.example/atom"/><published>2026-11-16T10:00:00Z</published><author><name>Reporter</name></author></entry></feed>'] as $xml) {
            $s = $this->source('rss'); $this->fake(['*' => Http::response($xml)]); $this->assertSame('success', $this->sync($s));
        }
        $this->assertSame(2, NewsArticle::count());
    }
    public function test_invalid_feed_is_isolated_from_healthy_source(): void
    {
        $bad = $this->source('rss'); $good = $this->source();
        $this->fake(['*/feed' => Http::response('<html>bad</html>'), '*/api' => Http::response([$this->row()])]);
        $result = app(Synchronizer::class)->run(null, true);
        $this->assertSame('failed', $result[$bad->id]); $this->assertSame('success', $result[$good->id]);
        $this->assertSame(1, NewsArticle::count());
    }
    public function test_timeout_is_bounded_and_logged(): void
    {
        $s = $this->source(); $this->fake(function () { throw new \Illuminate\Http\Client\ConnectionException('timeout'); });
        $this->assertSame('failed', $this->sync($s)); $this->assertSame(1, $s->fresh()->consecutive_failures);
        $this->assertNotNull(NewsSyncLog::first()->completed_at);
    }
    public function test_429_defers_manual_and_scheduled_retry(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([], 429, ['Retry-After' => '7200'])]);
        $this->assertSame('failed', $this->sync($s)); $this->assertSame('deferred', $this->sync($s)); Http::assertSentCount(1);
        $this->assertTrue($s->fresh()->retry_after_at->gt(now('UTC')->addHour()));
    }
    public function test_repeated_sync_preserves_id_and_editorial_choices(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([$this->row()])]); $this->sync($s);
        $a = NewsArticle::first(); $id = $a->id; $category = NewsCategory::where('slug', 'general-technology')->first();
        $a->update(['status' => 'hidden', 'is_featured' => true, 'category_id' => $category->id, 'editorial_category' => true,
            'ai_title' => 'Generated technology headline', 'ai_summary' => 'Generated summary',
            'ai_content' => 'Generated article text', 'ai_tags' => ['development'],
            'ai_meta_title' => 'Generated SEO title', 'ai_meta_description' => 'Generated SEO description',
            'editorial_status' => NewsArticle::STATUS_READY, 'rewrite_model' => 'test-model',
            'rewrite_version' => 1, 'rewrite_source_hash' => $a->content_hash,
            'rewrite_error' => null, 'rewrite_retry_at' => now('UTC')->addHour(), 'rewritten_at' => now('UTC')]);
        $row = $this->row(); $row['title'] = 'Updated source headline'; $this->fake(['*' => Http::response([$row])]);
        $this->sync($s); $a->refresh(); $this->assertSame(1, NewsArticle::count()); $this->assertSame($id, $a->id);
        $this->assertSame('hidden', $a->status); $this->assertTrue($a->is_featured); $this->assertSame($category->id, $a->category_id);
        $this->assertSame('Updated source headline', $a->title);
        $this->assertSame('Generated technology headline', $a->ai_title);
        $this->assertSame('Generated summary', $a->ai_summary);
        $this->assertSame('Generated article text', $a->ai_content);
        $this->assertSame(['development'], $a->ai_tags);
        $this->assertSame('Generated SEO title', $a->ai_meta_title);
        $this->assertSame('Generated SEO description', $a->ai_meta_description);
        $this->assertSame(NewsArticle::STATUS_PENDING, $a->editorial_status);
        $this->assertSame('test-model', $a->rewrite_model);
        $this->assertSame(1, $a->rewrite_version);
        $this->assertNotSame($a->content_hash, $a->rewrite_source_hash);
        $this->assertSame('UTC', $a->rewritten_at->timezoneName);
        $this->assertNull($a->rewrite_retry_at);
        $this->assertArrayNotHasKey('ai_title', $a->toArray());
        $this->assertArrayNotHasKey('rewrite_error', $a->toArray());
    }
    public function test_cross_source_tracking_urls_deduplicate(): void
    {
        $one = $this->source(); $two = $this->source(); $row = $this->row();
        $this->fake(['*' => Http::response([$row])]); $this->sync($one);
        $row['id'] = 555; $row['canonical_url'] .= '?utm_source=second'; $this->fake(['*' => Http::response([$row])]); $this->sync($two);
        $this->assertSame(1, NewsArticle::count()); $this->assertSame(2, DB::table('news_article_references')->count());
    }
    public function test_similar_titles_only_create_review_candidates(): void
    {
        $s = $this->source(); $a = $this->row(); $b = $this->row(2); $b['title'] = $a['title'];
        $this->fake(['*' => Http::response([$a, $b])]); $this->sync($s);
        $this->assertSame(2, NewsArticle::count()); $this->assertSame(1, DB::table('news_duplicate_candidates')->count());
    }
    public function test_hourly_due_window_and_distributed_lock(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([$this->row()])]); $this->sync($s);
        $this->assertSame('not_due', app(Synchronizer::class)->sync($s->fresh()));
        $s->update(['last_sync_at' => now('UTC')->subMinutes(61)]);
        DB::table('news_sync_locks')->insert(['name' => 'source-'.$s->id, 'owner' => 'another-server', 'expires_at' => now('UTC')->addHour()]);
        $this->assertSame('locked', $this->sync($s)); Http::assertSentCount(1);
        DB::table('news_sync_locks')->delete(); $this->assertSame('success', app(Synchronizer::class)->sync($s->fresh()));
        $this->assertSame(1, NewsArticle::count());
    }
    public function test_missing_dates_titles_and_bad_urls_are_skipped(): void
    {
        $s = $this->source(); $a = $this->row(); $a['published_at'] = null; $b = $this->row(2); $b['title'] = ''; $c = $this->row(3); $c['canonical_url'] = 'javascript:alert(1)';
        $this->fake(['*' => Http::response([$a, $b, $c])]); $this->sync($s);
        $this->assertSame(0, NewsArticle::count()); $this->assertSame(3, NewsSyncLog::first()->skipped_count);
    }
    public function test_deactivated_and_unpermitted_sources_are_not_requested(): void
    {
        $s = $this->source(); $s->update(['is_active' => false]);
        $this->assertSame([], app(Synchronizer::class)->run());
        $s->update(['is_active' => true, 'permissions_verified_at' => null]);
        $this->assertSame('disabled', $this->sync($s)); Http::assertNothingSent();
    }
    public function test_admin_authorization_and_persistent_manual_jobs(): void
    {
        $s = $this->source(); Queue::fake();
        $this->getJson('/api/admin/news')->assertUnauthorized();
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->postJson('/admin/technology-news/sync')->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->postJson('/admin/technology-news/sources/'.$s->id.'/sync')->assertOk();
        Queue::assertPushed(\App\Jobs\SyncTechnologyNews::class, fn ($job) => $job->sourceId === $s->id && $job->connection === 'technology-news');
    }
    public function test_admin_page_and_public_page_render_and_pagination_search_work(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response(array_map(fn ($id) => $this->row($id), range(1, 25)))]); $this->sync($s);
        $this->publishFixtures();
        $this->get('/technology-news')->assertOk()->assertSee('Read original');
        $this->getJson('/api/news?per_page=10&page=2')->assertOk()->assertJsonPath('meta.total', 25)->assertJsonCount(10, 'data');
        $this->getJson('/api/news/search?q=tools%2025')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/news?page=-1')->assertUnprocessable();
        $admin = User::factory()->create(['is_admin' => true]); $this->actingAs($admin)->get('/admin/technology-news')->assertOk()->assertSee('Synchronization logs');
    }
    public function test_cache_invalidated_by_sync_and_source_deactivation(): void
    {
        $s = $this->source(); $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
        $this->fake(['*' => Http::response([$this->row()])]); $this->sync($s);
        $this->publishFixtures();
        $this->getJson('/api/news')->assertJsonPath('meta.total', 1);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->patchJson('/admin/technology-news/sources/'.$s->id, ['name' => $s->name, 'is_active' => false, 'sync_interval_minutes' => 60, 'display_mode' => 'disabled'])->assertOk();
        $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
    }
    public function test_private_network_requests_and_xml_entities_are_rejected(): void
    {
        foreach (['http://example.com', 'https://127.0.0.1/feed', 'https://user:password@example.com', 'https://localhost/feed', 'https://example.com:8443/feed'] as $url) {
            try { app(SafeHttp::class)->validateUrl($url); $this->fail('Unsafe URL accepted.'); } catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $http = new class extends SafeHttp { protected function addresses(string $host): array { return ['127.0.0.1']; } };
        try { $http->get('https://example.com/feed'); $this->fail('Private DNS accepted.'); } catch (\RuntimeException $e) { $this->assertStringContainsString('refused', $e->getMessage()); }
        $this->expectException(\RuntimeException::class);
        app(Providers::class)->parseFeed('<!DOCTYPE rss [<!ENTITY x SYSTEM "file:///etc/passwd">]><rss>&x;</rss>');
    }
    public function test_redirects_and_malformed_payload_fail_safely(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1'])]);
        $this->assertSame('failed', $this->sync($s));
        $s->update(['retry_after_at' => null]); $this->fake(['*' => Http::response(['error' => 'unexpected shape'])]);
        $this->assertSame('failed', $this->sync($s));
    }
    public function test_transaction_failure_rolls_back_and_releases_lock(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([$this->row()])]);
        NewsArticle::creating(function () { throw new \RuntimeException('database failure'); });
        try {
            $this->assertSame('partial', $this->sync($s)); $this->assertSame(0, NewsArticle::count());
            $this->assertSame(0, DB::table('news_sync_locks')->count()); $this->assertSame(1, NewsSyncLog::first()->failed_count);
        } finally { NewsArticle::flushEventListeners(); }
    }
    public function test_hacker_news_does_not_import_discussions_or_submitter_as_author(): void
    {
        $s = $this->source('hackernews');
        $this->fake(['*/topstories.json' => Http::response([1, 2]), '*/newstories.json' => Http::response([2, 3]),
            '*/item/1.json' => Http::response(['id' => 1, 'type' => 'story', 'title' => 'Python release', 'url' => 'https://python.org/news', 'time' => now('UTC')->subHour()->timestamp, 'score' => 50, 'by' => 'submitter', 'text' => 'Discussion']),
            '*/item/2.json' => Http::response(['id' => 2, 'type' => 'story', 'deleted' => true]), '*/item/3.json' => Http::response(['id' => 3, 'type' => 'comment'])]);
        $this->assertSame('success', $this->sync($s)); $a = NewsArticle::firstOrFail();
        $this->assertNull($a->author_name); $this->assertNull($a->description); $this->assertSame('python.org', $a->publisher_name);
        $this->publishFixtures();
        $this->getJson('/api/news/trending')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('ranking', 'Hacker News score');
    }
    public function test_canonical_parameters_and_low_confidence_classification(): void
    {
        $n = new Normalizer();
        $this->assertSame('https://example.com/story?id=2&id=3', $n->url('https://EXAMPLE.com/story?id=2&utm_source=mail&id=3#top'));
        $this->assertSame('general-technology', $n->category(['title' => 'A neutral headline', 'tags' => [], 'description' => null]));
    }
    public function test_schedule_is_hourly_in_utc_and_installer_is_idempotent(): void
    {
        $count = NewsSource::count(); $this->artisan('news:install')->assertExitCode(0); $this->assertSame($count, NewsSource::count());
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $event = collect($events)->first(fn ($e) => str_contains($e->command ?? '', 'news:sync'));
        $this->assertNotNull($event); $this->assertSame('0 * * * *', $event->expression); $this->assertSame('UTC', $event->timezone);
    }
    public function test_small_scheduler_start_drift_does_not_skip_next_hour(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-17T12:00:00Z'));
        $s = $this->source(); $s->update(['last_sync_at' => \Carbon\Carbon::parse('2026-11-17T11:00:05Z')]);
        $this->fake(['*' => Http::response([$this->row()])]);
        $this->assertSame('success', app(Synchronizer::class)->sync($s->fresh()));
        $this->travelBack();
    }
    public function test_conditional_rss_304_preserves_archive(): void
    {
        $s = $this->source('rss'); $s->update(['etag' => 'etag-123']);
        $this->fake(['*' => Http::response('', 304)]);
        $this->assertSame('success', $this->sync($s));
        Http::assertSent(fn ($request) => $request->hasHeader('If-None-Match', 'etag-123'));
        $this->assertSame(0, NewsSyncLog::first()->fetched_count);
    }
    public function test_5xx_retries_are_bounded_and_invalid_calendar_dates_skipped(): void
    {
        $s = $this->source(); $this->fake(['*' => Http::response([], 500)]);
        $this->assertSame('failed', $this->sync($s)); Http::assertSentCount(3);
        $s->update(['retry_after_at' => null]); $r = $this->row(); $r['published_at'] = '2026-02-31T12:00:00Z';
        $this->fake(['*' => Http::response([$r])]); $this->assertSame('success', $this->sync($s));
        $this->assertSame(0, NewsArticle::count());
    }
    public function test_permitted_excerpt_similarity_only_flags_for_review(): void
    {
        $s = $this->source(); $a = $this->row(); $b = $this->row(2);
        $a['description'] = $b['description'] = 'A detailed permitted source description about new software development tools that help engineers build reliable applications with clear documentation testing deployment security monitoring and maintenance across distributed systems.';
        $a['title'] = 'First publisher headline'; $b['title'] = 'Different wording for the second headline';
        $this->fake(['*' => Http::response([$a, $b])]); $this->sync($s);
        $this->assertSame(2, NewsArticle::count()); $this->assertSame(1, DB::table('news_duplicate_candidates')->count());
    }
}
