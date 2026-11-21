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
            'services.nvidia.retry_attempts' => 1, 'services.nvidia.editorial_attempts' => 1]);
        $this->artisan('news:install')->assertExitCode(0);
        NewsSource::query()->update(['is_active' => false]);
        Http::preventStrayRequests();
    }

    private function article(array $attributes = []): NewsArticle
    {
        $source = NewsSource::create(['name' => 'Licensed technology publisher', 'source_type' => 'rss',
            'base_url' => 'https://publisher.example', 'feed_url' => 'https://publisher.example/feed', 'is_active' => true,
            'display_mode' => 'excerpt', 'allow_ai_rewrite' => true, 'allow_feed_content' => true, 'permissions_verified_at' => now('UTC'),
            'permission_reference' => 'https://publisher.example/licence']);
        return NewsArticle::create(array_merge(['source_id' => $source->id, 'source_article_id' => 'item-1',
            'title' => 'Python ships a new developer tool', 'description' => 'The tool helps developers test their Python applications.',
            'content' => 'Python has shipped a new tool to help developers test their applications.',
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
        return ['title' => 'New Python tool supports application testing', 'summary' => 'Python has shipped a new tool to help developers test their applications.',
            'content' => 'Python has shipped a new tool to help developers test their applications.'];
    }

    public function test_verified_rewrite_is_auto_approved_and_public_uses_ai_fields(): void
    {
        $a = $this->article(); $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => ['The paraphrase preserves the supplied facts.']]));
        $this->artisan('news:rewrite')->assertExitCode(0); $a->refresh();
        $this->assertSame('published', $a->editorial_status); $this->assertSame($a->content_hash, $a->rewrite_source_hash);
        $this->assertSame('Python ships a new developer tool', $a->title); $this->assertSame('test-model', $a->rewrite_model);
        $this->getJson('/api/news')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', $this->draft()['title']);
        $this->get('/technology-news')->assertOk()->assertSee($this->draft()['summary'])->assertSee('Latest stories');
        $this->get('/technology-news/'.$a->slug)->assertOk()->assertHeaderMissing('Location')->assertSee($this->draft()['content'])->assertSee('More technology news')->assertDontSee('Read original');
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
        $a = $this->article(); Http::fakeSequence()->push($this->ai(['title' => 'Python launches 999 tools', 'summary' => 'Python launches 999 tools.', 'content' => 'Python launches 999 tools for developers.']));
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
            return ['ai_title' => 'Old AI draft', 'ai_summary' => 'Old AI summary', 'ai_content' => 'Old full article body', 'rewrite_version' => 2];
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
            'content' => 'Python has shipped a new tool to help developers test their applications.',
            'url' => 'https://publisher.example/fresh', 'published_at' => now('UTC')->subHour()->toIso8601String(),
            'tags' => ['python'],
        ]]]);
        $this->app->instance(\App\Services\TechnologyNews\Providers::class, $providers);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->artisan('news:sync', ['--source' => $sourceId, '--force' => true])->assertExitCode(0);
        $this->assertSame(1, NewsArticle::count()); $this->assertSame(1, NewsArticle::published()->count());
        $this->getJson('/api/news')->assertJsonPath('data.0.title', $this->draft()['title']);
    }
    public function test_licensed_feed_body_produces_full_report_and_separate_preview(): void
    {
        $sourceSentence = 'Python shipped a developer tool that helps engineers test their applications and maintain reliable software. ';
        $a = $this->article(['content' => str_repeat($sourceSentence, 20)]);
        $a->source->update(['allow_feed_content' => true]);
        $draft = $this->draft(); $draft['content'] = implode("\n\n", array_fill(0, 3, trim(str_repeat($sourceSentence, 5))));
        $modelDraft = ['title' => $draft['title'], 'summary' => $draft['summary']];
        foreach (explode("\n\n", $draft['content']) as $i => $paragraph) { $modelDraft['paragraph_'.($i + 1)] = $paragraph; }
        Http::fakeSequence()->push($this->ai($modelDraft))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->artisan('news:rewrite')->assertExitCode(0); $a->refresh();
        $this->assertSame(2, $a->rewrite_version); $this->assertNotSame($a->ai_summary, $a->ai_content);
        $this->assertCount(3, explode("\n\n", $a->ai_content));
        $this->get('/technology-news/'.$a->slug)->assertOk()->assertSee('Back to technology news')
            ->assertSee('Source credits')->assertDontSee('AI-assisted report checked')->assertDontSee('Source licence & attribution');
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'] ?? '', 'maintain reliable software'));
    }
    public function test_article_detail_hides_pending_hidden_and_stale_drafts(): void
    {
        $a = $this->article(); $this->get('/technology-news/'.$a->slug)->assertNotFound();
        $a->update(['editorial_status' => 'published', 'ai_title' => 'Generated title', 'ai_summary' => 'Generated preview',
            'ai_content' => 'Generated article body.', 'rewrite_version' => 2, 'rewritten_at' => now('UTC'), 'rewrite_source_hash' => $a->content_hash]);
        $this->get('/technology-news/'.$a->slug)->assertOk()->assertSee('Generated article body.')->assertSee('noindex, follow');
        $a->update(['status' => 'hidden']); $this->get('/technology-news/'.$a->slug)->assertNotFound();
        $a->update(['status' => 'published', 'content_hash' => hash('sha256', 'changed')]);
        $this->get('/technology-news/'.$a->slug)->assertNotFound();
    }
    public function test_correctable_verification_failure_gets_one_bounded_revision(): void
    {
        $a = $this->article(); config(['services.nvidia.editorial_attempts' => 2]);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => false, 'reasons' => ['Keep original attribution.']]))
            ->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->artisan('news:rewrite')->assertExitCode(0);
        $this->assertSame('published', $a->fresh()->editorial_status); Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'] ?? '', 'Keep original attribution.'));
    }
    public function test_summary_only_sources_cannot_publish_full_news(): void
    {
        $a = $this->article(['content' => null]); Http::fake();
        $this->artisan('news:rewrite')->assertExitCode(1);
        $this->assertSame('failed', $a->fresh()->editorial_status);
        $this->assertStringContainsString('Full licensed source article text is required', $a->fresh()->rewrite_error);
        $this->get('/technology-news/'.$a->slug)->assertNotFound(); Http::assertNothingSent();
    }
    public function test_old_summary_only_rewrites_are_not_published_as_full_articles(): void
    {
        $a = $this->article(['editorial_status' => 'published', 'rewrite_version' => 1,
            'ai_title' => 'Old summary headline', 'ai_summary' => 'Old short summary', 'ai_content' => 'Old short summary',
            'rewritten_at' => now('UTC'), 'rewrite_source_hash' => hash('sha256', 'source')]);
        $this->get('/technology-news/'.$a->slug)->assertNotFound();
        $this->getJson('/api/news')->assertJsonPath('meta.total', 0);
    }
    public function test_original_author_and_license_are_kept_on_local_reports(): void
    {
        $a = $this->article(['author_name' => 'Original Author', 'editorial_status' => 'published', 'rewrite_version' => 2,
            'ai_title' => 'Verified local report', 'ai_summary' => 'Verified preview', 'ai_content' => 'Verified complete report.',
            'rewritten_at' => now('UTC'), 'rewrite_source_hash' => hash('sha256', 'source')]);
        $a->source->update(['name' => 'Fedora Magazine', 'license_name' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/']);
        $this->get('/technology-news/'.$a->slug)->assertOk()->assertHeaderMissing('Location')
            ->assertSeeText('By Original Author from Fedora Magazine')->assertSee('CC BY-SA 4.0')->assertSee('Edited by SetuGeo.');
        $this->getJson('/api/news/'.$a->slug)->assertJsonPath('data.license.name', 'CC BY-SA 4.0')
            ->assertJsonPath('data.license.url', 'https://creativecommons.org/licenses/by-sa/4.0/');
    }
    public function test_admin_index_is_simple_and_advanced_tools_have_separate_pages(): void
    {
        $a = $this->article(); $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/technology-news')->assertOk()->assertSee('Fetch News')->assertSee('Process Pending')
            ->assertSee('Source title')->assertSee('Regenerated title')->assertDontSee('Permission notes')->assertDontSee('Add RSS source');
        $this->get('/admin/technology-news/'.$a->id)->assertOk()->assertSee('Source article')->assertSee('Regenerated article');
        $this->get('/admin/technology-news/sources')->assertOk()->assertSee('Add RSS source');
        $this->get('/admin/technology-news/sync-logs')->assertOk()->assertSee('Synchronization logs');
        $this->get('/admin/technology-news/duplicates')->assertOk()->assertSee('Duplicate review');
        $this->get('/admin/technology-news?status=pending')->assertOk()->assertSee($a->title);
        $this->get('/admin/technology-news?status=failed')->assertOk()->assertDontSee($a->title);
    }
    public function test_admin_can_generate_and_unpublish_like_market_news(): void
    {
        $a = $this->article(); $admin = User::factory()->create(['is_admin' => true]);
        Http::fakeSequence()->push($this->ai($this->draft()))->push($this->ai(['approved' => true, 'reasons' => []]));
        $this->actingAs($admin)->postJson('/admin/technology-news/'.$a->id.'/regenerate')->assertOk()->assertJsonPath('success', true);
        $this->assertSame(1, NewsArticle::published()->count());
        $this->patchJson('/admin/technology-news/articles/'.$a->id, ['status' => 'hidden'])->assertOk();
        $this->get('/technology-news/'.$a->slug)->assertNotFound();
        $this->actingAs(User::factory()->create(['is_admin' => false]))->postJson('/admin/technology-news/'.$a->id.'/regenerate')->assertForbidden();
    }
}
