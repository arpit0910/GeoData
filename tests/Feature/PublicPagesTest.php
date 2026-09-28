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
    public function legacy_pricing_page_redirects_to_home_plans()
    {
        $response = $this->get('/pricing');
        $response->assertRedirect('/#plans');
    }

    /** @test */
    public function home_displays_only_free_and_business_offerings()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Free')
            ->assertSee('Business')
            ->assertSee('Create Free Account')
            ->assertSee('Request Business Plan')
            ->assertDontSee('Buy Now')
            ->assertDontSee('View Plans &amp; Pricing', false);
    }

    /** @test */
    public function business_plan_link_prefills_the_contact_form()
    {
        $response = $this->get('/contact?subject=Business%20Plan%20Enquiry&message=Please%20contact%20me');

        $response->assertOk()
            ->assertSee('value="Business Plan Enquiry"', false)
            ->assertSee('Please contact me');
    }

    /** @test */
    public function business_plan_enquiry_is_stored_as_a_lead()
    {
        $response = $this->post('/contact', [
            'first-name' => 'Jane',
            'last-name' => 'Doe',
            'email' => 'jane@example.com',
            'subject' => 'Business Plan Enquiry',
            'message' => 'We need enterprise API access for our team.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('website_queries', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Business Plan Enquiry',
            'status' => 'pending',
        ]);
    }
}
