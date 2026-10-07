<?php

namespace Tests\Feature;

use App\Models\MarketNews;
use App\Models\User;
use App\Services\GeminiNewsRewriter;
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
            ->assertSee('Draft changed a numerical fact.')
            ->assertDontSee('Published market story');
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
        $rewriter = $this->mock(GeminiNewsRewriter::class);
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

    public function test_non_admin_cannot_view_market_news_monitor(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.market-news.index'))
            ->assertRedirect('/');
    }
}
