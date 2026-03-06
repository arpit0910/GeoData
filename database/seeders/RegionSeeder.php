<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\RegionsImport;
use Maatwebsite\Excel\Facades\Excel;

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
            Excel::import(new RegionsImport, 'public/regions.csv');
            $this->command->info('Regions imported successfully.');
        } else {
            $this->command->error('regions.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
