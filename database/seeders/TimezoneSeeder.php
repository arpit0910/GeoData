<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\TimezonesImport;
use Maatwebsite\Excel\Facades\Excel;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/public/timezones.csv');

        if (file_exists($filePath)) {
            $this->command->info('Importing timezones from timezones.csv...');
            Excel::import(new TimezonesImport, 'public/timezones.csv');
            $this->command->info('Timezones imported successfully.');
        } else {
            $this->command->error('timezones.csv not found inside storage/app/public. Skipping seeder.');
        }
    }
}
