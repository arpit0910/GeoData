<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // Website FAQs
            [
                'question' => 'What is SetuGeo and how can it benefit my project?',
                'answer' => 'SetuGeo is a comprehensive geological and location intelligence platform that provides curated, high-accuracy data APIs for global countries, states, cities, and pincodes. It benefits developers and businesses by providing standardized, ready-to-use geographical datasets.',
                'visibility' => 'website',
                'status' => 'active',
                'order' => 1
            ],
            [
                'question' => 'Do you offer a free trial plan?',
                'answer' => 'Yes, we offer a Free Tier that includes limited monthly API credits, perfect for testing and small personal projects.',
                'visibility' => 'website',
                'status' => 'active',
                'order' => 2
            ],
            [
                'question' => 'How frequently is the geographical data updated?',
                'answer' => 'Our datasets are reviewed and updated on a monthly basis to reflect administrative changes, population shifts, and new infrastructural developments globally.',
                'visibility' => 'website',
                'status' => 'active',
                'order' => 3
            ],
            [
                'question' => 'Can I use SetuGeo for commercial applications?',
                'answer' => 'Absolutely! Our paid plans (Pro and Enterprise) are designed specifically for high-volume commercial production environments with guaranteed uptime and priority support.',
                'visibility' => 'website',
                'status' => 'active',
                'order' => 4
            ],

            // Dashboard FAQs
            [
                'question' => 'Where can I find my API Authentication Keys?',
                'answer' => 'You can access your Client Public and Secret Keys in the "API Keys" section of your dashboard sidebar. Remember to keep your Secret Key confidential.',
                'visibility' => 'dashboard',
                'status' => 'active',
                'order' => 1
            ],
            [
                'question' => 'How do I upgrade my current subscription plan?',
                'answer' => 'Navigate to the "Plans" or "Pricing" section from your dashboard, select the plan that best fits your needs, and follow the secure checkout process.',
                'visibility' => 'dashboard',
                'status' => 'active',
                'order' => 2
            ],
            [
                'question' => 'What happens if I exceed my monthly API credit limit?',
                'answer' => 'If you exceed your limit, API requests will temporarily return a 429 (Too Many Requests) error. You can either wait for your credits to renew or upgrade to a higher tier for immediate access.',
                'visibility' => 'dashboard',
                'status' => 'active',
                'order' => 3
            ],
            [
                'question' => 'How do I submit a technical support ticket?',
                'answer' => 'You can create a new support request through the "Help & Support" module in your dashboard. Our technical team typically responds within 24 hours.',
                'visibility' => 'dashboard',
                'status' => 'active',
                'order' => 4
            ]
        ];

        foreach ($faqs as $faqData) {
            Faq::updateOrCreate(
                ['question' => $faqData['question']],
                [
                    'answer' => $faqData['answer'],
                    'visibility' => $faqData['visibility'],
                    'status' => 1,
                    'order' => $faqData['order']
                ]
            );
        }
    }
}
