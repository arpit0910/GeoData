<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ini_set('memory_limit', '2048M');
        $filePath = storage_path('app/public/bank_ifsc.csv');
        if (!file_exists($filePath)) {
            $this->command->error("File not found: $filePath");
            return;
        }

        // 1. Get unique banks and seed them
        $this->command->info("Identifying unique banks...");
        $uniqueBanks = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 0, ",");
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (empty($data)) continue;
                $bankName = trim($data[0]);
                $slug = \Illuminate\Support\Str::slug($bankName);
                if ($bankName && !isset($uniqueBanks[$slug])) {
                    $uniqueBanks[$slug] = $bankName;
                }
            }
            fclose($handle);
        }

        $this->command->info("Seeding banks...");
        foreach ($uniqueBanks as $slug => $bankName) {
            \App\Models\Bank::firstOrCreate(
                ['slug' => $slug],
                ['name' => $bankName]
            );
        }
        $bankIds = \App\Models\Bank::pluck('id', 'slug')->toArray();

        // 2. Load States and Cities into memory
        $this->command->info("Loading states into memory...");
        $states = \App\Models\State::all()->mapWithKeys(function ($state) {
            return [strtolower($state->name) => $state->id];
        })->toArray();

        $this->command->info("Loading cities into memory (this can take significant RAM)...");
        $cities = [];
        \App\Models\City::chunk(10000, function ($chunk) use (&$cities) {
            foreach ($chunk as $city) {
                $cities[$city->state_id][strtolower($city->name)] = $city->id;
            }
        });

        // 3. Seed branches
        $this->command->info("Seeding bank branches...");
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 0, ",");
            // Header: BANK(0),IFSC(1),BRANCH(2),CENTRE(3),DISTRICT(4),STATE(5),ADDRESS(6),CONTACT(7),IMPS(8),RTGS(9),CITY(10),ISO3166(11),NEFT(12),MICR(13),UPI(14),SWIFT(15)
            
            $batch = [];
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (empty($data)) continue;

                $bankName = trim($data[0]);
                $slug = \Illuminate\Support\Str::slug($bankName);
                $stateName = strtolower(trim($data[5]));
                $cityName = strtolower(trim($data[10]));

                $bankId = $bankIds[$slug] ?? null;
                $stateId = $states[$stateName] ?? null;
                $cityId = null;

                if ($stateId) {
                    $cityId = $cities[$stateId][$cityName] ?? null;
                }

                // If city/state not found, skip or create? 
                // The prompt says "map the bank, state and city with the available ids... check name of city and state whether the name of city or state is in caps or small. And map the ids properly."
                // This implies they should exist. If not, I'll set to a default or skip?
                // But wait, many banks might be in cities not in our `cities` table?
                // I'll skip ones without city/state for now, or just leave NULL if allowed? 
                // Migration says constrained, so I need IDs.
                
                if (!$bankId || !$stateId || !$cityId) {
                    continue; 
                }

                $batch[] = [
                    'bank_id' => $bankId,
                    'ifsc' => $data[1],
                    'branch' => $data[2],
                    'micr' => $data[13] ?: null,
                    'address' => $data[6],
                    'contact' => $data[7],
                    'city_id' => $cityId,
                    'state_id' => $stateId,
                    'imps' => $data[8] === 'true',
                    'rtgs' => $data[9] === 'true',
                    'neft' => $data[12] === 'true',
                    'upi' => $data[14] === 'true',
                    'swift' => $data[15] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 1000) {
                    \App\Models\BankBranch::insert($batch);
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                \App\Models\BankBranch::insert($batch);
            }
            fclose($handle);
        }
        $this->command->info("Seeding completed.");
    }
}
