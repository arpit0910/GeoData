<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Timezone;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CitiesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
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

    public function model(array $row)
    {
        $row = array_map(function($val) { 
            return is_string($val) ? trim($val) : $val; 
        }, $row);

        $countryName = $row['country_name'] ?? null;
        $stateName = $row['state_name'] ?? null;
        $timezoneName = $row['timezone'] ?? null;

        $countryId = $countryName && isset($this->countries[$countryName]) ? $this->countries[$countryName] : null;
        
        $stateId = null;
        if ($countryId && $stateName) {
            $stateKey = $countryId . '_' . $stateName;
            $stateId = $this->states[$stateKey] ?? null;
        }

        $timezoneId = $timezoneName && isset($this->timezones[$timezoneName]) ? $this->timezones[$timezoneName] : null;

        // Ensure foreign keys aren't null if they are strictly required.
        // For 'country_id', it must not be null according to migration, so we should skip if it is null to avoid DB errors.
        if (!$countryId) {
            return null; // Skip invalid records
        }

        return new City([
            'name'         => $row['name'] ?? null,
            'state_id'     => $stateId,
            'country_id'   => $countryId,
            'latitude'     => isset($row['latitude']) && $row['latitude'] !== '' ? $row['latitude'] : null,
            'longitude'    => isset($row['longitude']) && $row['longitude'] !== '' ? $row['longitude'] : null,
            'type'         => $row['type'] ?? null,
            'timezone_id'  => $timezoneId,
            'wiki_data_id' => $row['wikidataid'] ?? null,
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
