<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AdminUserSeeder::class);
        $this->call([
            SubscriptionCatalogSeeder::class,
            RegionSeeder::class,
            SubRegionSeeder::class,
            CountrySeeder::class,
            TimezoneSeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PincodeSeeder::class,
            TicketCategorySeeder::class,
            FaqSeeder::class,
            BankSeeder::class,
            DemoCompanySeeder::class,
        ]);
    }
}
