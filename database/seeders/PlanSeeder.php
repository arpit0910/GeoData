<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\Plan;
use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SubscriptionFeatureSeeder::class);

        $products = [
            ['name' => 'Address only', 'monthly_amount' => 299, 'yearly_amount' => 2990, 'credits' => 50000,
                'terms' => 'Address, pincode, city, state, and location APIs.',
                'benefits' => ['Address APIs', 'Pincode APIs', 'Location lookup'],
                'features' => [SubscriptionFeature::MODULE_ADDRESS_API, SubscriptionFeature::MODULE_INDIA_PINCODE_API]],
            ['name' => 'MF and Stocks', 'monthly_amount' => 499, 'yearly_amount' => 4990, 'credits' => 100000,
                'terms' => 'Stocks, mutual funds, NAV, and market-data APIs.',
                'benefits' => ['Stocks APIs', 'Mutual fund APIs', 'Market analytics'],
                'features' => [SubscriptionFeature::MODULE_STOCKS_MUTUAL_FUNDS_API]],
            ['name' => 'Banks', 'monthly_amount' => 399, 'yearly_amount' => 3990, 'credits' => 75000,
                'terms' => 'IFSC, bank branch, banking, and currency APIs.',
                'benefits' => ['IFSC lookup', 'Bank branch APIs', 'Currency APIs'],
                'features' => [SubscriptionFeature::MODULE_BANKING_CURRENCY_API, SubscriptionFeature::MODULE_IFSC_API]],
            ['name' => 'All in one', 'monthly_amount' => 999, 'yearly_amount' => 9990, 'credits' => null,
                'terms' => 'Unlimited access to every available API module.',
                'benefits' => ['All API categories', 'Unlimited access', 'Priority support'],
                'features' => [SubscriptionFeature::MODULE_ALL_API]],
        ];

        $activePlanIds = [];
        foreach ($products as $product) {
            foreach (['monthly', 'yearly'] as $cycle) {
                $planData = [
                    'name' => $product['name'],
                    'amount' => $product[$cycle.'_amount'],
                    'discount_amount' => 0,
                    'billing_cycle' => $cycle,
                    // Yearly subscriptions receive this allowance each month.
                    'api_hits_limit' => $product['credits'],
                    'status' => 1,
                    'terms' => $product['terms'],
                    'benefits' => $product['benefits'],
                    'features' => $product['features'],
                ];
                $featureKeys = Arr::pull($planData, 'features');
                $plan = Plan::updateOrCreate(
                    ['name' => $product['name'], 'billing_cycle' => $cycle],
                    $planData
                );
                $activePlanIds[] = $plan->id;

                $plan->features()->sync(
                    SubscriptionFeature::query()->whereIn('key', $featureKeys)->pluck('id')
                );

                foreach ($product['benefits'] as $index => $benefitName) {
                    Benefit::updateOrCreate(
                        ['name' => $benefitName],
                        [
                            'slug' => Str::slug($benefitName),
                            'description' => $benefitName,
                            'is_active' => true,
                            'sort_order' => $index + 1,
                        ]
                    );
                }
                $plan->benefitItems()->sync(
                    Benefit::query()->whereIn('name', $product['benefits'])->pluck('id')
                );
            }
        }

        // Preserve legacy plans for payment history while removing them from
        // purchase and manual-assignment screens.
        Plan::query()->whereNotIn('id', $activePlanIds)->update(['status' => 0]);
    }
}
