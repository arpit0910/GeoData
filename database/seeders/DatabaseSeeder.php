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
            RegionSeeder::class,
            SubRegionSeeder::class,
            CountrySeeder::class,
            TimezoneSeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PlanSeeder::class,
            TicketCategorySeeder::class,
            FaqSeeder::class,
        ]);
    }
}
