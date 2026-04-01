<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\CitiesImport;
use Maatwebsite\Excel\Facades\Excel;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/cities.csv');
        
        if (file_exists($filePath)) {
            $this->command->info('Importing cities from cities.csv...');
            Excel::import(new CitiesImport, base_path('public/storage/cities.csv'));
            $this->command->info('Cities imported successfully.');
        } else {
            $this->command->error('cities.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
