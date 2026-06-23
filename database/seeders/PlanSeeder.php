<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\Plan;
use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Bronze',
                'amount' => 0,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 200,
                'status' => 1,
                'terms' => 'Free Bronze Plan with basic API access.',
                'benefits' => ['Basic Data Access', 'Standard Support'],
                'features' => [
                    SubscriptionFeature::MODULE_ADDRESS_API,
                ],
            ],
            [
                'name' => 'Silver',
                'amount' => 499,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 200000,
                'status' => 1,
                'terms' => 'Silver Monthly Plan with intermediate API access.',
                'benefits' => ['Extended Data Access', 'Priority Support'],
                'features' => [
                    SubscriptionFeature::MODULE_ADDRESS_API,
                    SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                ],
            ],
            [
                'name' => 'Silver',
                'amount' => 4999,
                'discount_amount' => 0,
                'billing_cycle' => 'yearly',
                'api_hits_limit' => 200000, 
                'status' => 1,
                'terms' => 'Silver Yearly Plan with high volume API access.',
                'benefits' => ['Extended Data Access', 'Priority Support','High Volume API access (Credits refreshes every month)'],
                'features' => [
                    SubscriptionFeature::MODULE_ADDRESS_API,
                    SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                ],
            ],
            [
                'name' => 'Gold',
                'amount' => 899,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => null,
                'status' => 1,
                'terms' => 'Gold Monthly Plan with unlimited API access.',
                'benefits' => ['Unlimited Data Access', '24/7 Premium Support', 'Dedicated Manager'],
                'features' => [
                    SubscriptionFeature::MODULE_ALL_API,
                ],
            ],
            [
                'name' => 'Gold',
                'amount' => 8999,
                'discount_amount' => 0,
                'billing_cycle' => 'yearly',
                'api_hits_limit' => null, 
                'status' => 1,
                'terms' => 'Gold Yearly Plan with unlimited API access.',
                'benefits' => ['Unlimited Data Access', '24/7 Premium Support', 'Dedicated Manager'],
                'features' => [
                    SubscriptionFeature::MODULE_ALL_API,
                ],
            ],
            [
                'name' => 'Address API Plan',
                'amount' => 299,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 50000,
                'status' => 1,
                'terms' => 'Address and geolocation APIs for lookup and validation use cases.',
                'benefits' => ['Address APIs', 'Pincode APIs', 'Location lookup'],
                'features' => [
                    SubscriptionFeature::MODULE_ADDRESS_API,
                ],
            ],
            [
                'name' => 'Banking & Currency Plan',
                'amount' => 399,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 75000,
                'status' => 1,
                'terms' => 'Banking and currency APIs for financial verification and conversion workflows.',
                'benefits' => ['IFSC lookup', 'Bank branch APIs', 'Currency APIs'],
                'features' => [
                    SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                ],
            ],
            [
                'name' => 'Stocks & Mutual Funds Plan',
                'amount' => 499,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 100000,
                'status' => 1,
                'terms' => 'Market data APIs for stocks, funds, NAVs, and market analytics.',
                'benefits' => ['Stocks APIs', 'Mutual fund APIs', 'Market analytics'],
                'features' => [
                    SubscriptionFeature::MODULE_STOCKS_MUTUAL_FUNDS_API,
                ],
            ],
            [
                'name' => 'Address + Banking Combo Plan',
                'amount' => 649,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 150000,
                'status' => 1,
                'terms' => 'Combined address and banking APIs for KYC and operational workflows.',
                'benefits' => ['Address APIs', 'Banking APIs', 'Currency APIs'],
                'features' => [
                    SubscriptionFeature::MODULE_ADDRESS_API,
                    SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                ],
            ],
            [
                'name' => 'Complete API Access Plan',
                'amount' => 999,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => null,
                'status' => 1,
                'terms' => 'Complete API access across every supported module.',
                'benefits' => ['All API categories', 'Unlimited access', 'Priority support'],
                'features' => [
                    SubscriptionFeature::MODULE_ALL_API,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $featureKeys = Arr::pull($planData, 'features', []);

            $plan = Plan::updateOrCreate(
                [
                    'name' => $planData['name'],
                    'billing_cycle' => $planData['billing_cycle'],
                ],
                $planData
            );

            if ($featureKeys !== []) {
                $featureIds = SubscriptionFeature::query()
                    ->whereIn('key', $featureKeys)
                    ->pluck('id');

                $plan->features()->sync($featureIds);
            }

            if (!empty($planData['benefits'])) {
                $benefitIds = Benefit::query()
                    ->whereIn('name', $planData['benefits'])
                    ->pluck('id');

                $plan->benefitItems()->sync($benefitIds);
            }
        }
    }
}
