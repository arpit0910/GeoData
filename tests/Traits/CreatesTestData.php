<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Region;
use App\Models\SubRegion;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Timezone;
use App\Models\Pincode;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\CurrencyConversion;
use App\Models\ApiLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

trait CreatesTestData
{
    /**
     * Create an admin user for dashboard tests.
     */
    protected function createAdminUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Admin',
            'email' => 'admin_' . Str::random(8) . '@test.com',
            'password' => Hash::make('Password@123'),
            'is_admin' => 1,
            'status' => 1,
            'company_name' => 'Test Corp',
            'phone' => '9876543210',
            'account_type' => 'client',
        ], $overrides));
    }

    /**
     * Create a regular user with profile completed.
     */
    protected function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user_' . Str::random(8) . '@test.com',
            'password' => Hash::make('Password@123'),
            'is_admin' => 0,
            'status' => 1,
            'company_name' => 'Client Company',
            'phone' => '9876543210',
            'account_type' => 'client',
        ], $overrides));
    }

    /**
     * Create a user without a completed profile.
     */
    protected function createIncompleteUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Incomplete User',
            'email' => 'incomplete_' . Str::random(8) . '@test.com',
            'password' => Hash::make('Password@123'),
            'is_admin' => 0,
            'status' => null,
            'company_name' => null,
            'phone' => null,
            'account_type' => 'client',
        ], $overrides));
    }

    /**
     * Create a user with an inactive status.
     */
    protected function createInactiveUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Inactive User',
            'email' => 'inactive_' . Str::random(8) . '@test.com',
            'password' => Hash::make('Password@123'),
            'is_admin' => 0,
            'status' => 0,
            'company_name' => 'Disabled Corp',
            'phone' => '1234567890',
            'account_type' => 'client',
        ], $overrides));
    }

    /**
     * Create a plan for subscription tests.
     */
    protected function createPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Starter Plan',
            'api_hits_limit' => 1000,
            'amount' => 499,
            'discount_amount' => 0,
            'status' => 1,
            'billing_cycle' => 'monthly',
            'terms' => 'Test plan terms',
            'benefits' => ['1000 API Calls', 'Email Support'],
        ], $overrides));
    }

    /**
     * Create an active subscription for a user.
     */
    protected function createActiveSubscription(User $user, Plan $plan = null, array $overrides = []): Subscription
    {
        if (!$plan) {
            $plan = $this->createPlan();
        }

        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'razorpay_order_id' => 'order_' . Str::random(14),
            'razorpay_payment_id' => 'pay_' . Str::random(14),
            'razorpay_signature' => Str::random(64),
            'amount_paid' => $plan->amount,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'total_credits' => $plan->api_hits_limit,
            'used_credits' => 0,
            'available_credits' => $plan->api_hits_limit,
        ], $overrides));
    }

    /**
     * Create an expired subscription.
     */
    protected function createExpiredSubscription(User $user, Plan $plan = null): Subscription
    {
        if (!$plan) {
            $plan = $this->createPlan();
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'razorpay_order_id' => 'order_' . Str::random(14),
            'razorpay_payment_id' => 'pay_' . Str::random(14),
            'razorpay_signature' => Str::random(64),
            'amount_paid' => $plan->amount,
            'status' => 'active',
            'expires_at' => now()->subDay(),
            'total_credits' => $plan->api_hits_limit,
            'used_credits' => $plan->api_hits_limit,
            'available_credits' => 0,
        ]);
    }

    /**
     * Create a Sanctum-authenticated user with active subscription and return token.
     */
    protected function createAuthenticatedApiUser(): array
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $subscription = $this->createActiveSubscription($user, $plan);
        $token = $user->createToken('setugeo-auth-token')->plainTextToken;

        return [
            'user' => $user,
            'plan' => $plan,
            'subscription' => $subscription,
            'token' => $token,
        ];
    }

    /**
     * Create a full geographical hierarchy: Region > SubRegion > Country > State > City > Pincode.
     */
    protected function createGeoHierarchy(): array
    {
        $region = Region::create(['name' => 'Asia']);
        $subRegion = SubRegion::create(['name' => 'Southern Asia', 'region_id' => $region->id]);

        $country = Country::create([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phonecode' => '91',
            'currency' => 'INR',
            'currency_name' => 'Indian Rupee',
            'currency_symbol' => '₹',
            'capital' => 'New Delhi',
            'region_id' => $region->id,
            'subregion_id' => $subRegion->id,
            'latitude' => 20.5937,
            'longitude' => 78.9629,
            'emoji' => '🇮🇳',
            'nationality' => 'Indian',
            'tld' => '.in',
            'population' => 1380004385,
            'gdp' => 2875142,
            'area_sq_km' => 3287263,
            'income_level' => 'Lower middle income',
            'driving_side' => 'left',
            'measurement_system' => 'metric',
            'tax_system' => 'GST',
            'standard_tax_rate' => 18.00,
            'is_oecd' => false,
            'is_eu' => false,
        ]);

        $timezone = Timezone::create([
            'zone_name' => 'Asia/Kolkata',
            'gmt_offset' => 19800,
            'gmt_offset_name' => 'UTC+05:30',
            'abbreviation' => 'IST',
            'tz_name' => 'India Standard Time',
            'country_id' => $country->id,
        ]);

        $state = State::create([
            'name' => 'Maharashtra',
            'country_id' => $country->id,
            'state_code' => 'MH',
            'iso2' => 'MH',
            'type' => 'state',
            'latitude' => 19.7515,
            'longitude' => 75.7139,
        ]);

        $city = City::create([
            'name' => 'Mumbai',
            'state_id' => $state->id,
            'country_id' => $country->id,
            'latitude' => 19.076,
            'longitude' => 72.8777,
            'type' => 'city',
        ]);

        $pincode = Pincode::create([
            'postal_code' => '400001',
            'city_id' => $city->id,
            'state_id' => $state->id,
            'country_id' => $country->id,
            'latitude' => 18.9398,
            'longitude' => 72.8354,
            'area' => 'Fort',
        ]);

        return compact('region', 'subRegion', 'country', 'timezone', 'state', 'city', 'pincode');
    }

    /**
     * Create bank and branch test data.
     */
    protected function createBankData(array $geo = null): array
    {
        if (!$geo) {
            $geo = $this->createGeoHierarchy();
        }

        $bank = Bank::create(['name' => 'State Bank of India', 'slug' => 'state-bank-of-india']);

        $branch = BankBranch::create([
            'bank_id' => $bank->id,
            'branch' => 'Fort Branch',
            'ifsc' => 'SBIN0000001',
            'micr' => '400002001',
            'address' => 'D N Road, Fort, Mumbai',
            'city_id' => $geo['city']->id,
            'state_id' => $geo['state']->id,
        ]);

        return array_merge($geo, compact('bank', 'branch'));
    }

    /**
     * Create currency conversion test data.
     */
    protected function createCurrencyData(): array
    {
        $country = Country::first() ?? $this->createGeoHierarchy()['country'];

        $eur = CurrencyConversion::create([
            'country_id' => $country->id,
            'currency' => 'EUR',
            'usd_conversion_rate' => 1.08,
            'inr_conversion_rate' => 89.50,
        ]);

        $gbp = CurrencyConversion::create([
            'country_id' => $country->id,
            'currency' => 'GBP',
            'usd_conversion_rate' => 1.27,
            'inr_conversion_rate' => 105.30,
        ]);

        $jpy = CurrencyConversion::create([
            'country_id' => $country->id,
            'currency' => 'JPY',
            'usd_conversion_rate' => 0.0067,
            'inr_conversion_rate' => 0.55,
        ]);

        return compact('eur', 'gbp', 'jpy');
    }

    /**
     * Create a second country for comparison tests.
     */
    protected function createSecondCountry(Region $region, SubRegion $subRegion): Country
    {
        return Country::create([
            'name' => 'Pakistan',
            'iso2' => 'PK',
            'iso3' => 'PAK',
            'phonecode' => '92',
            'currency' => 'PKR',
            'currency_name' => 'Pakistani Rupee',
            'currency_symbol' => '₨',
            'capital' => 'Islamabad',
            'region_id' => $region->id,
            'subregion_id' => $subRegion->id,
            'latitude' => 30.3753,
            'longitude' => 69.3451,
            'emoji' => '🇵🇰',
            'nationality' => 'Pakistani',
            'population' => 220892340,
            'gdp' => 278222,
            'area_sq_km' => 881913,
        ]);
    }
}
