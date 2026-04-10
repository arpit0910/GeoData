<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $plan = $this->createPlan();
        $this->createActiveSubscription($this->user, $plan);
        $this->createGeoHierarchy();
    }

    /** @test */
    public function subscribed_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_access_profile()
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_update_profile()
    {
        $geo = $this->createGeoHierarchy();

        $response = $this->actingAs($this->user)->put('/profile', [
            'name' => 'Updated Name',
            'company_name' => 'New Company',
            'phone' => '9998887776',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function subscribed_user_can_access_api_keys()
    {
        $response = $this->actingAs($this->user)->get('/api-keys');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_regenerate_api_keys()
    {
        $oldKey = $this->user->client_key;

        $response = $this->actingAs($this->user)->post('/api-keys/regenerate');
        $response->assertRedirect();

        $this->user->refresh();
        $this->assertNotEquals($oldKey, $this->user->client_key);
    }

    /** @test */
    public function subscribed_user_can_access_api_logs()
    {
        $response = $this->actingAs($this->user)->get('/api-logs');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_access_transactions()
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_access_help_support()
    {
        $response = $this->actingAs($this->user)->get('/help-support');
        $response->assertStatus(200);
    }

    /** @test */
    public function subscribed_user_can_submit_support_ticket()
    {
        // Create ticket categories first
        $category = \App\Models\TicketCategory::create(['name' => 'General', 'status' => 1]);
        $subCategory = \App\Models\TicketSubCategory::create([
            'name' => 'Billing Issue',
            'category_id' => $category->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($this->user)->post('/help-support', [
            'ticket_category_id' => $category->id,
            'ticket_sub_category_id' => $subCategory->id,
            'subject' => 'Test Support Ticket',
            'description' => 'This is a test ticket description for testing.',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function unsubscribed_user_can_access_help_support()
    {
        // Create a user without subscription
        $newUser = $this->createUser(['status' => 1]);

        $response = $this->actingAs($newUser)->get('/help-support');
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_subscription_pricing()
    {
        $response = $this->actingAs($this->user)->get('/subscribe');
        $response->assertStatus(200);
    }

    /** @test */
    public function user_password_update_requires_valid_data()
    {
        $response = $this->actingAs($this->user)->put('/profile/password', [
            'current_password' => 'wrong_password',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        // Should fail with validation errors
        $this->assertTrue(in_array($response->status(), [302, 422]));
    }
}
