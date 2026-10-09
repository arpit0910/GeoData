<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubscriptionCatalogSeeder extends Seeder
{
    /**
     * Seed subscription features, benefits, plans, and their pivot mappings.
     * Transaction histories are intentionally created only by real account,
     * payment, renewal, and credit-assignment activity.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);
    }
}
