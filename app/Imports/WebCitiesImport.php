<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Timezone;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebCitiesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $countries;
    private $states;
    private $timezones;

    public function __construct()
    {
        // Load relationships into memory to avoid N+1 queries during bulk import
        $this->countries = Country::pluck('id', 'name')->toArray();
        $this->timezones = Timezone::pluck('id', 'zone_name')->toArray();
        
        // Since state names might overlap between countries, we store a compound key: "{country_id}_{state_name}"
        $this->states = [];
        $allStates = State::select('id', 'name', 'country_id')->get();
        foreach ($allStates as $state) {
            $this->states[$state->country_id . '_' . $state->name] = $state->id;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowData = $row->map(function($val) { 
                return is_string($val) ? trim($val) : $val; 
            })->toArray();

            $countryName = $rowData['country_name'] ?? null;
            $stateName = $rowData['state_name'] ?? null;
            $timezoneName = $rowData['timezone'] ?? null;

            $countryId = $countryName && isset($this->countries[$countryName]) ? $this->countries[$countryName] : null;
            
            $stateId = null;
            if ($countryId && $stateName) {
                $stateKey = $countryId . '_' . $stateName;
                $stateId = $this->states[$stateKey] ?? null;
            }

            $timezoneId = $timezoneName && isset($this->timezones[$timezoneName]) ? $this->timezones[$timezoneName] : null;

            // Ensure foreign keys aren't null if they are strictly required.
            if (!$countryId || empty($rowData['name'])) {
                continue; // Skip invalid records
            }

            City::updateOrCreate(
                [
                    'name'       => $rowData['name'],
                    'country_id' => $countryId,
                    'state_id'   => $stateId,
                ],
                [
                    'latitude'     => isset($rowData['latitude']) && $rowData['latitude'] !== '' ? $rowData['latitude'] : null,
                    'longitude'    => isset($rowData['longitude']) && $rowData['longitude'] !== '' ? $rowData['longitude'] : null,
                    'type'         => $rowData['type'] ?? null,
                    'timezone_id'  => $timezoneId,
                    'wiki_data_id' => $rowData['wikidataid'] ?? null,
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
