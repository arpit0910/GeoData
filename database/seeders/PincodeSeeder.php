<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class PincodeSeeder extends Seeder
{
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

        // Map: iso2 => country_id
        $countries = Country::pluck('id', 'iso2')->toArray();

        // Dual state map: "countryId_name(lower)" and "countryId_iso2(lower)"
        $statesByCountryName  = [];
        $statesByCountryShort = [];
        foreach (State::select('id', 'name', 'iso2', 'country_id')->get() as $state) {
            $statesByCountryName[$state->country_id . '_' . mb_strtolower(trim($state->name))] = $state->id;
            if (!empty($state->iso2)) {
                $statesByCountryShort[$state->country_id . '_' . mb_strtolower(trim($state->iso2))] = $state->id;
            }
        }

        // Dual city map: state-level (preferred) + country-level (fallback)
        $citiesByState   = [];
        $citiesByCountry = [];
        foreach (City::select('id', 'name', 'state_id', 'country_id')->get() as $city) {
            $norm = mb_strtolower(trim($city->name));
            if ($city->state_id && !isset($citiesByState[$city->state_id . '_' . $norm])) {
                $citiesByState[$city->state_id . '_' . $norm] = $city->id;
            }
            if ($city->country_id && !isset($citiesByCountry[$city->country_id . '_' . $norm])) {
                $citiesByCountry[$city->country_id . '_' . $norm] = $city->id;
            }
        }

        $this->command->info("Relations loaded. Parsing CSV...");

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if ($header) {
            $header    = array_map('strtolower', array_map('trim', $header));
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }

        // CSV column semantics (GeoNames format):
        //   city        => area / locality / neighbourhood  (e.g. "Mansarovar")
        //   county      => actual city name                 (e.g. "Jaipur")
        //   community   => actual city name (alternate)     (e.g. "Jaipur")
        //
        // So: city_id is resolved from county → community → city (in that priority order)
        //     area    is stored directly from the 'city' column

        $chunkSize = 1000;
        $batch     = [];
        $count     = 0;
        $skipped   = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($header) !== count($row)) {
                $skipped++;
                continue;
            }

            $rowData = array_map('trim', array_combine($header, $row));

            // ── 1. Country ───────────────────────────────────────────────────
            $countryCode = $rowData['country'] ?? '';
            if (!$countryCode || !isset($countries[$countryCode])) {
                $skipped++;
                continue;
            }
            $countryId = $countries[$countryCode];

            if (empty($rowData['postal_code'])) {
                $skipped++;
                continue;
            }

            // ── 2. State ─────────────────────────────────────────────────────
            $stateId   = null;
            $stateName = $rowData['state'] ?? '';
            if ($stateName) {
                $stateId = $statesByCountryName[$countryId . '_' . mb_strtolower($stateName)]
                    ?? $statesByCountryShort[$countryId . '_' . mb_strtolower($stateName)]
                    ?? null;
            }
            if (!$stateId && !empty($rowData['short_state'])) {
                $sk      = $countryId . '_' . mb_strtolower($rowData['short_state']);
                $stateId = $statesByCountryShort[$sk] ?? $statesByCountryName[$sk] ?? null;
            }

            // ── 3. Area (locality) — raw 'city' column value ─────────────────
            $area = $rowData['city'] ?: null;

            // ── 4. City ID — resolved from county → community → city column ──
            // Priority: county > community > city (city is actually the area/locality)
            $cityId = null;
            foreach (['county', 'community', 'city'] as $cityCol) {
                $candidateName = $rowData[$cityCol] ?? '';
                if (!$candidateName) continue;

                $norm = mb_strtolower($candidateName);

                // State-level match first
                if ($stateId) {
                    $cityId = $citiesByState[$stateId . '_' . $norm] ?? null;
                }
                // Country-level fallback
                if (!$cityId) {
                    $cityId = $citiesByCountry[$countryId . '_' . $norm] ?? null;
                }

                if ($cityId) break;
            }

            // ── 5. Build batch row ───────────────────────────────────────────
            $batch[] = [
                'postal_code'     => $rowData['postal_code'],
                'country_id'      => $countryId,
                'state_id'        => $stateId,
                'city_id'         => $cityId,
                'area'            => $area,
                'short_state'     => $rowData['short_state']     ?: null,
                'county'          => $rowData['county']          ?: null,
                'short_county'    => $rowData['short_county']    ?: null,
                'community'       => $rowData['community']       ?: null,
                'short_community' => $rowData['short_community'] ?: null,
                'latitude'        => $rowData['latitude']        ?: null,
                'longitude'       => $rowData['longitude']       ?: null,
                'accuracy'        => $rowData['accuracy']        ?: null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            if (count($batch) >= $chunkSize) {
                DB::table('pincodes')->upsert(
                    $batch,
                    ['postal_code', 'country_id'],
                    ['state_id', 'city_id', 'area', 'short_state', 'county', 'short_county',
                     'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
                );
                $count += count($batch);
                $batch  = [];
                if ($count % 50000 === 0) {
                    $this->command->info("Imported {$count} records...");
                }
            }
        }

        if (count($batch) > 0) {
            DB::table('pincodes')->upsert(
                $batch,
                ['postal_code', 'country_id'],
                ['state_id', 'city_id', 'area', 'short_state', 'county', 'short_county',
                 'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
            );
            $count += count($batch);
        }

        fclose($handle);
        $this->command->info("Completed. Imported {$count} pincodes. Skipped {$skipped} malformed rows.");
    }
}
