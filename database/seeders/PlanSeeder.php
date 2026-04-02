<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Plan::truncate();
        Schema::enableForeignKeyConstraints();

        $plans = [
            [
                'name' => 'Bronze',
                'amount' => 0,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 5000,
                'status' => 1,
                'terms' => 'Free Bronze Plan with basic API access.',
                'benefits' => ['Basic Data Access', 'Standard Support'],
            ],
            [
                'name' => 'Silver',
                'amount' => 299,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => 250000,
                'status' => 1,
                'terms' => 'Silver Monthly Plan with intermediate API access.',
                'benefits' => ['Extended Data Access', 'Priority Support'],
            ],
            [
                'name' => 'Silver',
                'amount' => 1999,
                'discount_amount' => 0,
                'billing_cycle' => 'yearly',
                'api_hits_limit' => 3000000, 
                'status' => 1,
                'terms' => 'Silver Yearly Plan with high volume API access.',
                'benefits' => ['Extended Data Access', 'Priority Support'],
            ],
            [
                'name' => 'Gold',
                'amount' => 599,
                'discount_amount' => 0,
                'billing_cycle' => 'monthly',
                'api_hits_limit' => null,
                'status' => 1,
                'terms' => 'Gold Monthly Plan with unlimited API access.',
                'benefits' => ['Unlimited Data Access', '24/7 Premium Support', 'Dedicated Manager'],
            ],
            [
                'name' => 'Gold',
                'amount' => 3999,
                'discount_amount' => 0,
                'billing_cycle' => 'yearly',
                'api_hits_limit' => null, 
                'status' => 1,
                'terms' => 'Gold Yearly Plan with unlimited API access.',
                'benefits' => ['Unlimited Data Access', '24/7 Premium Support', 'Dedicated Manager'],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
