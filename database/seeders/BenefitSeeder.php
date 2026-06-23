<?php

namespace Database\Seeders;

use App\Models\Benefit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BenefitSeeder extends Seeder
{
    public function run()
    {
        $benefits = [
            'Basic Data Access',
            'Standard Support',
            'Extended Data Access',
            'Priority Support',
            'High Volume API access (Credits refreshes every month)',
            'Unlimited Data Access',
            '24/7 Premium Support',
            'Dedicated Manager',
            'Address APIs',
            'Pincode APIs',
            'Location lookup',
            'IFSC lookup',
            'Bank branch APIs',
            'Currency APIs',
            'Stocks APIs',
            'Mutual fund APIs',
            'Market analytics',
            'Banking APIs',
            'All API categories',
            'Unlimited access',
        ];

        foreach ($benefits as $index => $benefitName) {
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
    }
}
