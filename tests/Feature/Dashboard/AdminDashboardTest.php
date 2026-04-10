<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Country;
use App\Models\Region;
use App\Models\SubRegion;
use App\Models\State;
use App\Models\City;
use App\Models\Plan;
use App\Models\Bank;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $admin;
    protected $geoData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->geoData = $this->createGeoHierarchy();
    }

    // ─── USER MANAGEMENT ──────────────────────────────────────────────

    /** @test */
    public function admin_can_view_user_list()
    {
        $response = $this->actingAs($this->admin)->get('/user/list');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_create_user_form()
    {
        $response = $this->actingAs($this->admin)->get('/user/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $user = $this->createUser();

        $response = $this->actingAs($this->admin)->get('/user/show/' . $user->id);
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_edit_user()
    {
        $user = $this->createUser();

        $response = $this->actingAs($this->admin)->get('/user/edit/' . $user->id);
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_toggle_user_status()
    {
        $user = $this->createUser(['status' => 1]);

        $response = $this->actingAs($this->admin)->post('/user/toggle-status/' . $user->id);
        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotEquals(1, $user->status);
    }

    // ─── COUNTRY MANAGEMENT ───────────────────────────────────────────

    /** @test */
    public function admin_can_view_countries()
    {
        $response = $this->actingAs($this->admin)->get('/countries');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_country()
    {
        $response = $this->actingAs($this->admin)->get('/countries/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_edit_country()
    {
        $response = $this->actingAs($this->admin)->get('/countries/' . $this->geoData['country']->id . '/edit');
        $response->assertStatus(200);
    }

    // ─── REGION MANAGEMENT ────────────────────────────────────────────

    /** @test */
    public function admin_can_view_regions()
    {
        $response = $this->actingAs($this->admin)->get('/regions');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_region()
    {
        $response = $this->actingAs($this->admin)->get('/regions/create');
        $response->assertStatus(200);
    }

    // ─── SUBREGION MANAGEMENT ─────────────────────────────────────────

    /** @test */
    public function admin_can_view_subregions()
    {
        $response = $this->actingAs($this->admin)->get('/subregions');
        $response->assertStatus(200);
    }

    // ─── STATE MANAGEMENT ─────────────────────────────────────────────

    /** @test */
    public function admin_can_view_states()
    {
        $response = $this->actingAs($this->admin)->get('/states');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_state()
    {
        $response = $this->actingAs($this->admin)->get('/states/create');
        $response->assertStatus(200);
    }

    // ─── CITY MANAGEMENT ──────────────────────────────────────────────

    /** @test */
    public function admin_can_view_cities()
    {
        $response = $this->actingAs($this->admin)->get('/cities');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_city()
    {
        $response = $this->actingAs($this->admin)->get('/cities/create');
        $response->assertStatus(200);
    }

    // ─── PLAN MANAGEMENT ──────────────────────────────────────────────

    /** @test */
    public function admin_can_view_plans()
    {
        $response = $this->actingAs($this->admin)->get('/plans');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_plan()
    {
        $response = $this->actingAs($this->admin)->get('/plans/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_store_plan()
    {
        $response = $this->actingAs($this->admin)->post('/plans', [
            'name' => 'Test Gold Plan',
            'api_hits_limit' => 5000,
            'amount' => 999,
            'discount_amount' => 0,
            'status' => 1,
            'billing_cycle' => 'monthly',
            'terms' => 'Test plan terms',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', ['name' => 'Test Gold Plan']);
    }

    /** @test */
    public function admin_can_toggle_plan_status()
    {
        $plan = $this->createPlan();

        $response = $this->actingAs($this->admin)->post('/plans/' . $plan->id . '/toggle-status');
        $response->assertStatus(200);
    }

    // ─── PINCODE MANAGEMENT ───────────────────────────────────────────

    /** @test */
    public function admin_can_view_pincodes()
    {
        $response = $this->actingAs($this->admin)->get('/pincodes');
        $response->assertStatus(200);
    }

    // ─── BANK MANAGEMENT ──────────────────────────────────────────────

    /** @test */
    public function admin_can_view_banks()
    {
        $response = $this->actingAs($this->admin)->get('/banks');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_bank()
    {
        $response = $this->actingAs($this->admin)->get('/banks/create');
        $response->assertStatus(200);
    }

    // ─── SUBSCRIPTION MANAGEMENT ──────────────────────────────────────

    /** @test */
    public function admin_can_view_subscriptions()
    {
        $response = $this->actingAs($this->admin)->get('/admin/subscriptions');
        $response->assertStatus(200);
    }

    // ─── WEBSITE QUERIES ──────────────────────────────────────────────

    /** @test */
    public function admin_can_view_website_queries()
    {
        $response = $this->actingAs($this->admin)->get('/website-queries');
        $response->assertStatus(200);
    }

    // ─── COUPON MANAGEMENT ────────────────────────────────────────────

    /** @test */
    public function admin_can_view_coupons()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
    }

    // ─── TRANSACTION HISTORY ──────────────────────────────────────────

    /** @test */
    public function admin_can_view_transactions()
    {
        $response = $this->actingAs($this->admin)->get('/admin-transactions');
        $response->assertStatus(200);
    }

    // ─── TICKET MANAGEMENT ────────────────────────────────────────────

    /** @test */
    public function admin_can_view_ticket_categories()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ticket-categories.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_tickets()
    {
        $response = $this->actingAs($this->admin)->get('/tickets');
        $response->assertStatus(200);
    }

    // ─── FAQ MANAGEMENT ───────────────────────────────────────────────

    /** @test */
    public function admin_can_view_faqs()
    {
        $response = $this->actingAs($this->admin)->get('/faqs');
        $response->assertStatus(200);
    }

    // ─── CURRENCY CONVERSIONS ─────────────────────────────────────────

    /** @test */
    public function admin_can_view_currency_conversions()
    {
        $response = $this->actingAs($this->admin)->get('/currency-conversions');
        $response->assertStatus(200);
    }

    // ─── TIMEZONE MANAGEMENT ──────────────────────────────────────────

    /** @test */
    public function admin_can_view_timezones()
    {
        $response = $this->actingAs($this->admin)->get('/timezones');
        $response->assertStatus(200);
    }
}
