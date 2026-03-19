<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Bronz',
                'amount' => 0,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 100,
                'status' => 1,
                'terms' => 'Free Bronz Plan with limited API access.',
                'benefits' => ['Basic Data Access', 'Standard Support'],
            ],
            [
                'name' => 'Silver',
                'amount' => 99,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 10000,
                'status' => 1,
                'terms' => 'Silver Plan with extended API access.',
                'benefits' => ['Extended Data Access', 'Priority Support'],
            ],
            [
                'name' => 'Gold',
                'amount' => 199,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => null, // unlimited
                'status' => 1,
                'terms' => 'Gold Plan with unlimited API access.',
                'benefits' => ['Unlimited Data Access', '24/7 Premium Support', 'Dedicated Manager'],
            ]
        ];

        foreach ($plans as $planData) {
            if (!Plan::where('name', $planData['name'])->exists()) {
                Plan::create($planData);
            }
        }
    }
}
