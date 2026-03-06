<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\CountriesImport;
use Maatwebsite\Excel\Facades\Excel;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/countries.csv');
        
        if (file_exists($filePath)) {
            $this->command->info('Importing countries from countries.csv...');
            Excel::import(new CountriesImport, 'public/countries.csv');
            $this->command->info('Countries imported successfully.');
        } else {
            $this->command->error('countries.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
