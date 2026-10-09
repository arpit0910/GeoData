<?php

namespace Tests\Feature;

use Database\Seeders\SubscriptionCatalogSeeder;
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
        $response->assertOk()
            ->assertSee('Choose the access your business needs');
    }

    /** @test */
    public function home_displays_the_subscription_catalog()
    {
        $this->seed(SubscriptionCatalogSeeder::class);
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Address only')
            ->assertSee('MF and Stocks')
            ->assertSee('Banks')
            ->assertSee('All in one')
            ->assertSee('View monthly')
            ->assertSee(route('pricing'), false);
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
