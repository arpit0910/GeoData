<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\Country;
use App\Imports\StatesImport;
use Maatwebsite\Excel\Facades\Excel;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/states.csv');
        
        if (file_exists($filePath)) {
            $this->command->info('Importing states from states.csv...');
            Excel::import(new StatesImport, base_path('public/storage/states.csv'));
            $this->command->info('States imported successfully.');
        } else {
            $this->command->error('states.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
