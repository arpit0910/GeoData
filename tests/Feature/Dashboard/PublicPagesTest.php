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
    public function pricing_page_loads()
    {
        $response = $this->get('/pricing');
        $response->assertStatus(200);
    }

    /** @test */
    public function docs_page_loads()
    {
        $response = $this->get('/docs');
        $response->assertStatus(200);
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
