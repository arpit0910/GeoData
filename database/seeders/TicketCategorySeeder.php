<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technical Support',
                'subcategories' => [
                    'API Integration Issues',
                    'Data Accuracy Feedback',
                    'Service Downtime',
                    'Other Technical Errors'
                ]
            ],
            [
                'name' => 'Billing & Payments',
                'subcategories' => [
                    'Subscription Cancellation',
                    'Payment Failure',
                    'Invoice Requests',
                    'Coupon & Discount Issues'
                ]
            ],
            [
                'name' => 'Account & Access',
                'subcategories' => [
                    'Password Reset',
                    'Profile Update Issues',
                    'API Key Reset',
                    'Account Security'
                ]
            ],
            [
                'name' => 'Sales & General',
                'subcategories' => [
                    'Custom Plan Inquiry',
                    'Partnership Requests',
                    'General Feedback',
                    'Career Inquiry'
                ]
            ]
        ];

        foreach ($categories as $catData) {
            $category = TicketCategory::updateOrCreate(
                ['name' => $catData['name']],
                ['status' => 1]
            );

            foreach ($catData['subcategories'] as $subName) {
                TicketSubCategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $subName
                    ],
                    ['status' => 1]
                );
            }
        }
    }
}
