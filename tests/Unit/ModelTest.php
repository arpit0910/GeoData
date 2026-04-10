<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Subscription;

class ModelTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    // ─── USER MODEL ───────────────────────────────────────────────────

    /** @test */
    public function user_generates_client_keys_on_creation()
    {
        $user = $this->createUser();

        $this->assertNotNull($user->client_key);
        $this->assertNotNull($user->client_secret);
        $this->assertStringStartsWith('ck_', $user->client_key);
        $this->assertStringStartsWith('secret_', $user->client_secret);
    }

    /** @test */
    public function user_hides_sensitive_attributes()
    {
        $user = $this->createUser();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('client_secret', $array);
        $this->assertArrayNotHasKey('active_access_token', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function user_defaults_to_client_account_type()
    {
        $user = $this->createUser();
        $this->assertEquals('client', $user->account_type);
    }

    /** @test */
    public function user_has_active_subscription_returns_true()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $this->createActiveSubscription($user, $plan);

        $this->assertTrue($user->hasActiveSubscription());
    }

    /** @test */
    public function user_has_active_subscription_returns_false_when_expired()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $this->createExpiredSubscription($user, $plan);

        $this->assertFalse($user->hasActiveSubscription());
    }

    /** @test */
    public function user_has_active_subscription_returns_false_when_none()
    {
        $user = $this->createUser();
        $this->assertFalse($user->hasActiveSubscription());
    }

    /** @test */
    public function user_has_subscriptions_relationship()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $this->createActiveSubscription($user, $plan);

        $this->assertCount(1, $user->subscriptions);
    }

    /** @test */
    public function user_has_api_logs_relationship()
    {
        $user = $this->createUser();
        $this->assertCount(0, $user->apiLogs);
    }

    /** @test */
    public function user_format_date_method_works()
    {
        $user = $this->createUser();
        $result = $user->formatDate(now());
        $this->assertNotEquals('N/A', $result);
    }

    /** @test */
    public function user_format_date_returns_na_for_null()
    {
        $user = $this->createUser();
        $this->assertEquals('N/A', $user->formatDate(null));
    }

    // ─── COUNTRY MODEL ────────────────────────────────────────────────

    /** @test */
    public function country_has_region_relationship()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['country']->Region);
        $this->assertEquals('Asia', $geo['country']->Region->name);
    }

    /** @test */
    public function country_has_subregion_relationship()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['country']->SubRegion);
    }

    /** @test */
    public function country_has_timezones_relationship()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertCount(1, $geo['country']->timezones);
    }

    // ─── STATE MODEL ──────────────────────────────────────────────────

    /** @test */
    public function state_belongs_to_country()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['state']->Country);
        $this->assertEquals('India', $geo['state']->Country->name);
    }

    // ─── CITY MODEL ───────────────────────────────────────────────────

    /** @test */
    public function city_belongs_to_state()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['city']->State);
        $this->assertEquals('Maharashtra', $geo['city']->State->name);
    }

    /** @test */
    public function city_belongs_to_country()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['city']->Country);
    }

    // ─── PINCODE MODEL ────────────────────────────────────────────────

    /** @test */
    public function pincode_belongs_to_city_state_country()
    {
        $geo = $this->createGeoHierarchy();
        $this->assertNotNull($geo['pincode']->city);
        $this->assertNotNull($geo['pincode']->state);
        $this->assertNotNull($geo['pincode']->country);
    }

    // ─── SUBSCRIPTION MODEL ───────────────────────────────────────────

    /** @test */
    public function subscription_belongs_to_user()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $sub = $this->createActiveSubscription($user, $plan);

        $this->assertEquals($user->id, $sub->user->id);
    }

    /** @test */
    public function subscription_belongs_to_plan()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $sub = $this->createActiveSubscription($user, $plan);

        $this->assertEquals($plan->id, $sub->plan->id);
    }

    /** @test */
    public function subscription_casts_expires_at_to_datetime()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $sub = $this->createActiveSubscription($user, $plan);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sub->expires_at);
    }

    // ─── PLAN MODEL ───────────────────────────────────────────────────

    /** @test */
    public function plan_casts_benefits_to_array()
    {
        $plan = $this->createPlan();
        $this->assertIsArray($plan->benefits);
    }

    /** @test */
    public function plan_has_subscriptions_relationship()
    {
        $plan = $this->createPlan();
        $user = $this->createUser();
        $this->createActiveSubscription($user, $plan);

        $this->assertCount(1, $plan->subscriptions);
    }

    // ─── API LOG MODEL ────────────────────────────────────────────────

    /** @test */
    public function api_log_casts_correctly()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $sub = $this->createActiveSubscription($user, $plan);

        $log = \App\Models\ApiLog::create([
            'user_id' => $user->id,
            'subscription_id' => $sub->id,
            'endpoint' => 'api/v1/regions',
            'method' => 'GET',
            'status_code' => 200,
            'ip_address' => '127.0.0.1',
            'request_payload' => ['name' => 'test'],
            'credit_deducted' => true,
        ]);

        $this->assertIsArray($log->request_payload);
        $this->assertIsBool($log->credit_deducted);
    }

    // ─── BANK / BRANCH MODEL ──────────────────────────────────────────

    /** @test */
    public function bank_has_branches_relationship()
    {
        $data = $this->createBankData();
        $this->assertCount(1, $data['bank']->branches);
    }

    /** @test */
    public function branch_belongs_to_bank()
    {
        $data = $this->createBankData();
        $this->assertNotNull($data['branch']->bank);
        $this->assertEquals('State Bank of India', $data['branch']->bank->name);
    }

    // ─── CURRENCY CONVERSION MODEL ────────────────────────────────────

    /** @test */
    public function currency_conversion_stores_rates()
    {
        $currencies = $this->createCurrencyData();
        $this->assertEquals('EUR', $currencies['eur']->currency);
        $this->assertEquals(1.08, $currencies['eur']->usd_conversion_rate);
    }
}
