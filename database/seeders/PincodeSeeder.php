<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class PincodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $path = storage_path('app/public/allCountryPincodes.csv');
        
        if (!file_exists($path)) {
            $this->command->warn('Seeder skipped: allCountryPincodes.csv not found in storage/app/public.');
            return;
        }

        $this->command->info("Loading relations into memory for high-speed Pincode seeding...");
        $countries = Country::pluck('id', 'iso2')->toArray();
        
        $states = [];
        foreach (State::select('id', 'name', 'country_id')->get() as $state) {
            $states[$state->country_id . '_' . $state->name] = $state->id;
        }
        $cities = [];
        foreach (City::select('id', 'name', 'state_id')->get() as $city) {
            $cities[$city->state_id . '_' . $city->name] = $city->id;
        }

        $this->command->info("Relations loaded. Parsing CSV...");

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if ($header) {
            $header = array_map('strtolower', $header);
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }

        $chunkSize = 1000;
        $batch = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($header) !== count($row)) continue;
            
            $rowData = array_combine($header, $row);

            $countryCode = $rowData['country'] ?? null;
            if (!$countryCode || !isset($countries[$countryCode])) {
                continue;
            }
            $countryId = $countries[$countryCode];

            $stateName = $rowData['state'] ?? null;
            $stateKey = $countryId . '_' . $stateName;
            $stateId = $stateName && isset($states[$stateKey]) ? $states[$stateKey] : null;

            $cityName = $rowData['city'] ?? null;
            $cityKey = $stateId . '_' . $cityName;
            $cityId = $cityName && isset($cities[$cityKey]) ? $cities[$cityKey] : null;

            if (empty($rowData['postal_code'])) {
                continue;
            }

            $batch[] = [
                'postal_code'     => $rowData['postal_code'],
                'country_id'      => $countryId,
                'state_id'        => $stateId,
                'city_id'         => $cityId,
                'short_state'     => $rowData['short_state'] ?? null,
                'county'          => $rowData['county'] ?? null,
                'short_county'    => $rowData['short_county'] ?? null,
                'community'       => $rowData['community'] ?? null,
                'short_community' => $rowData['short_community'] ?? null,
                'latitude'        => $rowData['latitude'] ?? null,
                'longitude'       => $rowData['longitude'] ?? null,
                'accuracy'        => $rowData['accuracy'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            if (count($batch) >= $chunkSize) {
                DB::table('pincodes')->upsert(
                    $batch,
                    ['postal_code', 'country_id'],
                    ['state_id', 'city_id', 'short_state', 'county', 'short_county', 'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
                );
                $count += count($batch);
                $batch = [];
                if ($count % 50000 === 0) {
                    $this->command->info("Imported {$count} records...");
                }
            }
        }

        if (count($batch) > 0) {
            DB::table('pincodes')->upsert(
                $batch,
                ['postal_code', 'country_id'],
                ['state_id', 'city_id', 'short_state', 'county', 'short_county', 'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
            );
            $count += count($batch);
        }

        fclose($handle);
        $this->command->info("Completed. Imported {$count} Pincodes.");
    }
}
