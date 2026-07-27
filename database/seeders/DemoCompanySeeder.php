<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Pincode;
use App\Models\Plan;
use App\Models\State;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::query()
            ->where('name', 'Complete API Access Plan')
            ->where('billing_cycle', 'monthly')
            ->first()
            ?? Plan::query()
                ->where('name', 'Gold')
                ->where('billing_cycle', 'monthly')
                ->first()
            ?? Plan::query()->where('status', 1)->orderByDesc('amount')->first();

        if (!$plan) {
            $this->command?->warn('Ethnic Treasures seeder skipped because no active plan exists.');

            return;
        }

        $country = Country::query()->where('name', 'India')->first() ?? Country::query()->first();
        $state = State::query()
            ->when($country, fn ($query) => $query->where('country_id', $country->id))
            ->where('name', 'Rajasthan')
            ->first()
            ?? State::query()->when($country, fn ($query) => $query->where('country_id', $country->id))->first();
        $city = City::query()
            ->when($state, fn ($query) => $query->where('state_id', $state->id))
            ->where('name', 'Jaipur')
            ->first()
            ?? City::query()->when($state, fn ($query) => $query->where('state_id', $state->id))->first();
        $pincode = Pincode::query()
            ->when($city, fn ($query) => $query->where('city_id', $city->id))
            ->where('postal_code', '302020')
            ->first()
            ?? Pincode::query()->when($city, fn ($query) => $query->where('city_id', $city->id))->first();

        $user = User::query()->updateOrCreate(
            ['email' => 'demo@ethnictreasures.in'],
            [
                'name' => 'Ethnic Treasures Demo',
                'password' => Hash::make('Demo@12345'),
                'status' => 1,
                'is_admin' => 0,
                'account_type' => 'client',
                'company_name' => 'Ethnic Treasures',
                'company_website' => 'https://ethnictreasures.in/',
                'phone' => '8955718147',
                'gst_number' => '08AAYPV4747R1Z5',
                'address_line_1' => '36/98, Kiran Path, Mansarovar',
                'address_line_2' => 'Jaipur, Rajasthan',
                'country_id' => $country?->id,
                'state_id' => $state?->id,
                'city_id' => $city?->id,
                'pincode' => $pincode?->postal_code ?? '302020',
                'timezone' => 'Asia/Kolkata',
                'plan_id' => $plan->id,
            ]
        );

        if (empty($user->client_key) || empty($user->client_secret)) {
            $user->forceFill([
                'client_key' => $user->client_key ?: 'ck_' . Str::random(32),
                'client_secret' => $user->client_secret ?: 'secret_' . Str::random(60),
            ])->save();
        }

        Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        Subscription::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
            ],
            [
                'razorpay_order_id' => 'demo-order-ethnic-treasures',
                'razorpay_payment_id' => 'demo-payment-ethnic-treasures',
                'razorpay_signature' => 'demo-signature-ethnic-treasures',
                'amount_paid' => $plan->amount,
                'expires_at' => now()->addMonth(),
                'total_credits' => $plan->api_hits_limit,
                'used_credits' => 0,
                'available_credits' => $plan->api_hits_limit,
                'last_credit_refresh' => now(),
            ]
        );

        $this->command?->info('Ethnic Treasures demo company is ready: demo@ethnictreasures.in / Demo@12345');
    }
}
