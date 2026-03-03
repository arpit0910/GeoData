<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/regions.csv');
        
        if (file_exists($filePath)) {
            $this->command->info('Importing regions from regions.csv...');
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\RegionsImport, 'public/regions.csv');
            $this->command->info('Regions imported successfully.');
        } else {
            $this->command->error('regions.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
