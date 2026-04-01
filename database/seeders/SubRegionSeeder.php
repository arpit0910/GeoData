<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\SubRegionsImport;
use Maatwebsite\Excel\Facades\Excel;

class SubRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/subregions.csv');
        
        if (file_exists($filePath)) {
            $this->command->info('Importing sub regions from subregions.csv...');
            Excel::import(new SubRegionsImport, base_path('public/storage/subregions.csv'));
            $this->command->info('Sub Regions imported successfully.');
        } else {
            $this->command->error('subregions.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
