<?php

namespace Database\Seeders;

use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;

class SubscriptionFeatureSeeder extends Seeder
{
    public function run()
    {
        $features = [
            [
                'key' => SubscriptionFeature::MODULE_ADDRESS_API,
                'name' => 'Address API',
                'description' => 'Pincode, address, city/state, and location API access.',
                'is_active' => true,
            ],
            [
                'key' => SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                'name' => 'Banking & Currency API',
                'description' => 'IFSC, bank branch, and currency API access.',
                'is_active' => true,
            ],
            [
                'key' => SubscriptionFeature::MODULE_STOCKS_MUTUAL_FUNDS_API,
                'name' => 'Stocks & Mutual Funds API',
                'description' => 'Stocks, mutual funds, NAV, and market API access.',
                'is_active' => true,
            ],
            [
                'key' => SubscriptionFeature::MODULE_ALL_API,
                'name' => 'All API Access',
                'description' => 'Access to all current and future API modules.',
                'is_active' => true,
            ],
            [
                'key' => SubscriptionFeature::MODULE_INDIA_PINCODE_API,
                'name' => 'India Pincode API',
                'description' => 'Single-object Indian pincode detail lookup.',
                'is_active' => true,
            ],
            [
                'key' => SubscriptionFeature::MODULE_IFSC_API,
                'name' => 'IFSC API',
                'description' => 'Single bank branch lookup by IFSC code.',
                'is_active' => true,
            ],
        ];

        foreach ($features as $feature) {
            SubscriptionFeature::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }
    }
}
