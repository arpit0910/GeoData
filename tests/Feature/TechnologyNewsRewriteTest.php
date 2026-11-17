<?php

namespace Tests\Feature;

use App\Models\{NewsArticle, NewsCategory, NewsSource, User};
use App\Services\TechnologyNews\{ArticleRewriter, NewsCache};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Http};
use Tests\TestCase;

class TechnologyNewsRewriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.nvidia.api_key' => 'test-secret',
            'services.nvidia.endpoint' => 'https://ai.example/v1/chat/completions', 'services.nvidia.model' => 'test-model',
            'services.nvidia.fallback_models' => '', 'services.nvidia.verification_models' => 'test-model',
            'services.nvidia.retry_attempts' => 1]);
        $this->artisan('news:install')->assertExitCode(0);
        NewsSource::query()->update(['is_active' => false]);
        Http::preventStrayRequests();
    }

    private function article(array $attributes = []): NewsArticle
    {
        $source = NewsSource::create(['name' => 'Licensed technology publisher', 'source_type' => 'rss',
            'base_url' => 'https://publisher.example', 'feed_url' => 'https://publisher.example/feed', 'is_active' => true,
            'display_mode' => 'excerpt', 'allow_ai_rewrite' => true, 'permissions_verified_at' => now('UTC'),
            'permission_reference' => 'https://publisher.example/licence']);
        return NewsArticle::create(array_merge(['source_id' => $source->id, 'source_article_id' => 'item-1',
            'title' => 'Python ships a new developer tool', 'description' => 'The tool helps developers test their Python applications.',
            'canonical_url' => 'https://publisher.example/story', 'original_url' => 'https://publisher.example/story',
            'url_hash' => hash('sha256', 'story'), 'content_hash' => hash('sha256', 'source'), 'title_hash' => hash('sha256', 'title'),
            'publisher_name' => $source->name, 'slug' => 'python-developer-tool',
            'category_id' => NewsCategory::where('slug', 'programming-development')->value('id'), 'tags' => ['python'],
            'published_at' => now('UTC')->subHour(), 'fetched_at' => now('UTC'), 'last_seen_at' => now('UTC')], $attributes));
    }

    private function ai(array $json): array
    {
        return ['choices' => [['message' => ['content' => json_encode($json)], 'finish_reason' => 'stop']]];
    }

    private function draft(): array
    {
        return ['title' => 'New Python tool supports application testing', 'summary' => 'Python has shipped a new tool to help developers test their applications.'];
    }

    public function test_verified_rewrite_is_auto_approved_and_public_uses_ai_fields(): void
    {
        $a = $this->article(); $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => ['The paraphrase preserves the supplied facts.']]));
        $this->artisan('news:rewrite')->assertExitCode(0); $a->refresh();
        $this->assertSame('published', $a->editorial_status); $this->assertSame($a->content_hash, $a->rewrite_source_hash);
        $this->assertSame('Python ships a new developer tool', $a->title); $this->assertSame('test-model', $a->rewrite_model);
        $this->getJson('/api/news')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', $this->draft()['title']);
        $this->get('/technology-news')->assertOk()->assertSee($this->draft()['summary'])->assertSee('Read original');
        $this->artisan('news:rewrite')->assertExitCode(0); Http::assertSentCount(2);
    }

    public function test_failed_fact_check_never_publishes_and_is_deferred(): void
    {
        $a = $this->article(); Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => false, 'reasons' => ['Unsupported fact.']]));
        $this->artisan('news:rewrite')->assertExitCode(1); $a->refresh();
        $this->assertSame('failed', $a->editorial_status); $this->assertNull($a->ai_title); $this->assertTrue($a->rewrite_retry_at->isFuture());
        $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
        $this->artisan('news:rewrite')->assertExitCode(0); Http::assertSentCount(2);
    }

    public function test_unsupported_numbers_fail_before_verification(): void
    {
        $a = $this->article(); Http::fakeSequence()->push($this->ai(['title' => 'Python launches 999 tools', 'summary' => 'Python launches 999 tools.']));
        $this->artisan('news:rewrite')->assertExitCode(1); $this->assertSame('failed', $a->fresh()->editorial_status); Http::assertSentCount(1);
    }

    public function test_rate_limit_sets_retry_after_and_does_not_publish(): void
    {
        $a = $this->article(); Http::fake(['*' => Http::response([], 429, ['Retry-After' => '3600'])]);
        $this->artisan('news:rewrite')->assertExitCode(1);
        $this->assertTrue($a->fresh()->rewrite_retry_at->gte(now('UTC')->addMinutes(59)));
        $this->assertSame(0, NewsArticle::published()->count());
    }

    public function test_hidden_processing_and_unlicensed_stories_are_protected(): void
    {
        $a = $this->article(['status' => 'hidden']);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->artisan('news:rewrite')->assertExitCode(0); $this->assertSame('hidden', $a->fresh()->status);
        $this->assertSame(0, NewsArticle::published()->count());
        $a->update(['editorial_status' => 'processing']);
        $this->artisan('news:rewrite')->assertExitCode(0); Http::assertSentCount(2);
        $a->update(['editorial_status' => 'pending']); $a->source->update(['allow_ai_rewrite' => false]);
        $this->artisan('news:rewrite')->assertExitCode(0); Http::assertSentCount(2);
    }

    public function test_source_change_during_generation_cannot_publish_stale_copy(): void
    {
        $a = $this->article();
        $mock = \Mockery::mock(ArticleRewriter::class);
        $mock->shouldReceive('generateDraft')->once()->andReturnUsing(function ($article) {
            NewsArticle::whereKey($article->id)->update(['content_hash' => hash('sha256', 'changed'), 'editorial_status' => 'pending']);
            return ['ai_title' => 'Old AI draft', 'ai_summary' => 'Old AI summary'];
        });
        $this->app->instance(ArticleRewriter::class, $mock);
        $this->artisan('news:rewrite')->assertExitCode(0);
        $this->assertNull($a->fresh()->ai_title); $this->assertSame('pending', $a->fresh()->editorial_status);
    }

    public function test_cron_page_includes_both_jobs_and_schedule_drains_backlog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.crons.index'))->assertOk()->assertSee('news:sync')->assertSee('news:rewrite');
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertSame('0 * * * *', $events->first(fn ($e) => str_contains($e->command ?? '', 'news:sync'))->expression);
        $this->assertSame('*/5 * * * *', $events->first(fn ($e) => str_contains($e->command ?? '', 'news:rewrite'))->expression);
    }
    public function test_hourly_fetch_command_runs_ai_and_publishes_after_fetching(): void
    {
        $a = $this->article(); $sourceId = $a->source_id; $a->delete();
        $providers = \Mockery::mock(\App\Services\TechnologyNews\Providers::class);
        $providers->shouldReceive('fetch')->once()->andReturn(['items' => [[
            'id' => 'fresh-story', 'title' => 'Python ships a new developer tool',
            'description' => 'The tool helps developers test their Python applications.',
            'url' => 'https://publisher.example/fresh', 'published_at' => now('UTC')->subHour()->toIso8601String(),
            'tags' => ['python'],
        ]]]);
        $this->app->instance(\App\Services\TechnologyNews\Providers::class, $providers);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->artisan('news:sync', ['--source' => $sourceId, '--force' => true])->assertExitCode(0);
        $this->assertSame(1, NewsArticle::count()); $this->assertSame(1, NewsArticle::published()->count());
        $this->getJson('/api/news')->assertJsonPath('data.0.title', $this->draft()['title']);
    }
}
