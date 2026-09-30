<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    /** @test */
    public function homepage_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test */
    public function about_page_loads()
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
    }

    /** @test */
    public function contact_page_loads()
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
    }

    /** @test */
    public function legacy_pricing_page_redirects_to_home_plans()
    {
        $response = $this->get('/pricing');
        $response->assertRedirect('/#plans');
    }

    /** @test */
    public function docs_page_loads()
    {
        $response = $this->get('/docs');
        $response->assertStatus(200)
            ->assertSee('NSE/BSE Holiday Calendar')
            ->assertSee('/api/v1/market-calendar/holidays?exchange=all&amp;type=holiday', false);
    }

    /** @test */
    public function postman_collection_includes_the_nse_bse_holiday_request()
    {
        $collection = json_decode(file_get_contents(public_path('postman_collection.json')), true, 512, JSON_THROW_ON_ERROR);
        $encoded = json_encode($collection, JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString(
            '{{baseUrl}}/market-calendar/holidays?exchange=all&type=holiday',
            $encoded
        );
        $this->assertStringContainsString('official NSE and BSE equity-market holidays', $encoded);
        $this->assertStringContainsString('Republic Day', $encoded);
    }

    /** @test */
    public function status_page_loads()
    {
        $response = $this->get('/status');
        $response->assertStatus(200);
    }

    /** @test */
    public function privacy_page_loads()
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200);
    }

    /** @test */
    public function terms_page_loads()
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
    }

    /** @test */
    public function faq_page_loads()
    {
        $response = $this->get('/faq');
        $response->assertStatus(200);
    }

    /** @test */
    public function landing_v1_loads()
    {
        $response = $this->get('/landing-v1');
        $response->assertStatus(200);
    }

    /** @test */
    public function landing_v2_loads()
    {
        $response = $this->get('/landing-v2');
        $response->assertStatus(200);
    }

    /** @test */
    public function landing_v3_loads()
    {
        $response = $this->get('/landing-v3');
        $response->assertStatus(200);
    }

    /** @test */
    public function contact_form_validates_input()
    {
        $response = $this->post('/contact', []);
        // Should either redirect with errors or show validation errors
        $this->assertTrue(in_array($response->status(), [302, 422]));
    }
}
