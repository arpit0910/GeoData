<?php

namespace Tests\Feature;

use App\Models\MarketNews;
use App\Models\User;
use App\Services\NvidiaNewsRewriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMarketNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_monitor_and_filter_market_news(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        MarketNews::create([
            'title' => 'Published market story',
            'original_title' => 'Published source story',
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);
        MarketNews::create([
            'title' => 'Failed market story',
            'original_title' => 'Failed source story',
            'editorial_status' => MarketNews::STATUS_FAILED,
            'is_published' => false,
            'rewrite_error' => 'Draft changed a numerical fact.',
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.market-news.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Market News')
            ->assertSee('Failed source story')
            ->assertSee('Upstox title')
            ->assertSee('Regenerated title')
            ->assertSee('View Details')
            ->assertSee('Draft changed a numerical fact.')
            ->assertDontSee('Published market story');
    }

    public function test_admin_can_view_full_comparison_and_bulk_approve_ready_drafts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $readyStories = collect([1, 2])->map(fn ($number) => MarketNews::create([
            'title' => "Regenerated headline {$number} with verified market details",
            'summary' => "Regenerated article {$number} with verified market details.",
            'original_title' => "Upstox source headline {$number}",
            'original_summary' => "Upstox source summary {$number}.",
            'original_content' => str_repeat("Full source article {$number}. ", 20),
            'editorial_status' => MarketNews::STATUS_READY,
            'is_published' => false,
            'rewritten_at' => now(),
            'published_at' => now(),
        ]));
        $pending = MarketNews::create([
            'title' => 'Pending source headline',
            'original_title' => 'Pending source headline',
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.market-news.show', $readyStories->first()))
            ->assertOk()
            ->assertSee('Upstox source headline 1')
            ->assertSee('Regenerated headline 1')
            ->assertSee('Extracted source article')
            ->assertSee('Approve & Publish', false);

        $this->post(route('admin.market-news.bulk-approve'), [
            'news_ids' => $readyStories->pluck('id')->push($pending->id)->all(),
        ])->assertRedirect()->assertSessionHas('success');

        foreach ($readyStories as $story) {
            $this->assertDatabaseHas('market_news', [
                'id' => $story->id,
                'editorial_status' => MarketNews::STATUS_PUBLISHED,
                'is_published' => true,
                'reviewed_by' => $admin->id,
            ]);
        }
        $this->assertDatabaseHas('market_news', [
            'id' => $pending->id,
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
        ]);
    }

    public function test_admin_can_regenerate_approve_and_unpublish_news(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $news = MarketNews::create([
            'title' => 'Original provider headline',
            'summary' => 'Original provider summary.',
            'original_title' => 'Original provider headline',
            'original_summary' => 'Original provider summary.',
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]);
        $rewriter = $this->mock(NvidiaNewsRewriter::class);
        $rewriter->shouldReceive('rewrite')->once()->andReturnUsing(function (MarketNews $item) {
            $item->forceFill([
                'title' => 'Regenerated market headline ready for administrator approval',
                'summary' => 'This regenerated article is ready for administrator approval.',
                'editorial_status' => MarketNews::STATUS_READY,
                'is_published' => false,
                'rewritten_at' => now(),
            ])->save();

            return ['title' => $item->title, 'summary' => $item->summary];
        });

        $this->actingAs($admin)
            ->post(route('admin.market-news.regenerate', $news))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('market_news', [
            'id' => $news->id,
            'editorial_status' => MarketNews::STATUS_READY,
            'is_published' => false,
        ]);

        $this->post(route('admin.market-news.approve', $news))
            ->assertRedirect()
            ->assertSessionHas('success');
        $news->refresh();
        $this->assertSame(MarketNews::STATUS_PUBLISHED, $news->editorial_status);
        $this->assertTrue($news->is_published);
        $this->assertSame($admin->id, (int) $news->reviewed_by);
        $this->assertNotNull($news->reviewed_at);
        $this->get('/market/news')->assertSee('Regenerated market headline');

        $this->post(route('admin.market-news.unpublish', $news))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('market_news', [
            'id' => $news->id,
            'editorial_status' => MarketNews::STATUS_READY,
            'is_published' => false,
        ]);
    }

    public function test_admin_can_select_and_generate_multiple_news_stories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $stories = collect([1, 2])->map(fn ($number) => MarketNews::create([
            'title' => "Source headline {$number}",
            'summary' => "Source summary {$number}.",
            'original_title' => "Source headline {$number}",
            'original_summary' => "Source summary {$number}.",
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]));

        $rewriter = $this->mock(NvidiaNewsRewriter::class);
        $rewriter->shouldReceive('rewrite')->twice()->andReturnUsing(function (MarketNews $story) {
            $story->forceFill([
                'title' => "Verified generated headline for news story {$story->id}",
                'summary' => 'A detailed generated article that has completed the configured verification workflow.',
                'editorial_status' => MarketNews::STATUS_READY,
                'is_published' => false,
                'rewritten_at' => now(),
            ])->save();

            return ['title' => $story->title, 'summary' => $story->summary];
        });

        $this->actingAs($admin)
            ->get(route('admin.market-news.index'))
            ->assertOk()
            ->assertSee('Generate Selected')
            ->assertSee('select-all-news');

        $this->post(route('admin.market-news.bulk-regenerate'), [
            'news_ids' => $stories->pluck('id')->all(),
        ])->assertRedirect()->assertSessionHas('success');

        foreach ($stories as $story) {
            $this->assertDatabaseHas('market_news', [
                'id' => $story->id,
                'editorial_status' => MarketNews::STATUS_READY,
                'is_published' => false,
            ]);
        }
    }

    public function test_admin_can_generate_one_news_story_as_json_for_sequential_bulk_processing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $news = MarketNews::create([
            'title' => 'Source headline',
            'original_title' => 'Source headline',
            'original_summary' => 'Source summary.',
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]);

        $rewriter = $this->mock(NvidiaNewsRewriter::class);
        $rewriter->shouldReceive('rewrite')->once()->andReturnUsing(function (MarketNews $story) {
            $story->forceFill([
                'title' => 'Verified generated headline for sequential processing',
                'summary' => 'A generated article that passed the verification workflow.',
                'editorial_status' => MarketNews::STATUS_READY,
                'rewritten_at' => now(),
            ])->save();

            return ['title' => $story->title, 'summary' => $story->summary];
        });

        $this->actingAs($admin)
            ->postJson(route('admin.market-news.regenerate', $news))
            ->assertOk()
            ->assertJsonPath('message', "News #{$news->id} was regenerated and is ready for approval.");
    }

    public function test_non_admin_cannot_view_market_news_monitor(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.market-news.index'))
            ->assertRedirect('/');
    }
}
