<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class SubscriptionFeatureAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'subscription:address_api'])
            ->get('/api/test/address-feature', fn () => sendResponse(['module' => 'address'], 'OK'));

        Route::middleware(['auth:sanctum', 'subscription:banking_currency_api'])
            ->get('/api/test/banking-feature', fn () => sendResponse(['module' => 'banking'], 'OK'));

        Route::middleware(['auth:sanctum', 'subscription:stocks_mutual_funds_api'])
            ->get('/api/test/stocks-feature', fn () => sendResponse(['module' => 'stocks'], 'OK'));
    }

    /** @test */
    public function user_with_address_subscription_can_access_address_routes()
    {
        $user = $this->createUser();
        $plan = $this->createPlan(['feature_keys' => [SubscriptionFeature::MODULE_ADDRESS_API]]);
        $this->createActiveSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/address-feature')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function user_with_address_subscription_cannot_access_banking_routes()
    {
        $user = $this->createUser();
        $plan = $this->createPlan(['feature_keys' => [SubscriptionFeature::MODULE_ADDRESS_API]]);
        $this->createActiveSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/banking-feature')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your current plan does not include this API category.',
                'required_module' => SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                'data' => null,
            ]);
    }

    /** @test */
    public function combo_subscription_can_access_multiple_allowed_modules()
    {
        $user = $this->createUser();
        $plan = $this->createPlan([
            'feature_keys' => [
                SubscriptionFeature::MODULE_ADDRESS_API,
                SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
            ],
        ]);
        $this->createActiveSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/address-feature')->assertOk();
        $this->getJson('/api/test/banking-feature')->assertOk();
    }

    /** @test */
    public function all_api_plan_can_access_every_route()
    {
        $user = $this->createUser();
        $plan = $this->createPlan(['feature_keys' => [SubscriptionFeature::MODULE_ALL_API]]);
        $this->createActiveSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/address-feature')->assertOk();
        $this->getJson('/api/test/banking-feature')->assertOk();
        $this->getJson('/api/test/stocks-feature')->assertOk();
    }

    /** @test */
    public function expired_subscription_is_blocked()
    {
        $user = $this->createUser();
        $plan = $this->createPlan(['feature_keys' => [SubscriptionFeature::MODULE_ADDRESS_API]]);
        $this->createExpiredSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/address-feature')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your subscription has expired.',
                'required_module' => SubscriptionFeature::MODULE_ADDRESS_API,
                'data' => null,
            ]);
    }

    /** @test */
    public function user_without_subscription_is_blocked()
    {
        $user = $this->createUser();

        Sanctum::actingAs($user);

        $this->getJson('/api/test/address-feature')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Subscription required to access this API.',
                'required_module' => SubscriptionFeature::MODULE_ADDRESS_API,
                'data' => null,
            ]);
    }

    /** @test */
    public function unknown_module_configuration_returns_server_error()
    {
        Route::middleware(['auth:sanctum', 'subscription:unknown_module'])
            ->get('/api/test/unknown-feature', fn () => sendResponse(['module' => 'unknown'], 'OK'));

        $user = $this->createUser();
        $plan = $this->createPlan(['feature_keys' => [SubscriptionFeature::MODULE_ALL_API]]);
        $this->createActiveSubscription($user, $plan);

        Sanctum::actingAs($user);

        $this->getJson('/api/test/unknown-feature')
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Subscription module configuration is invalid.',
                'required_module' => 'unknown_module',
                'data' => null,
            ]);
    }
}
