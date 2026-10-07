<?php

namespace Tests\Feature;

use App\Models\MarketNews;
use App\Models\User;
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
            ->assertSee('Failed market story')
            ->assertSee('Draft changed a numerical fact.')
            ->assertDontSee('Published market story');
    }

    public function test_non_admin_cannot_view_market_news_monitor(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.market-news.index'))
            ->assertRedirect('/');
    }
}
