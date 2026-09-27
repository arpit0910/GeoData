<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_public_market_sections_load_independently(): void
    {
        foreach ([
            '/market',
            '/market/stocks',
            '/market/mutual-funds',
            '/market/news',
            '/market/fundamentals',
            '/market/corporate-actions',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
