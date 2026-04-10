<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function home_page_is_accessible()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test */
    public function pricing_page_is_accessible()
    {
        $response = $this->get('/pricing');
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
