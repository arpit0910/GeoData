<?php

namespace App\Imports;

use App\Models\Pincode;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PincodesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $countries;
    private $states;
    private $cities;

    public function __construct()
    {
        $this->countries = Country::pluck('id', 'iso2')->toArray();
        
        $this->states = [];
        $allStates = State::select('id', 'name', 'country_id')->get();
        foreach ($allStates as $state) {
            $this->states[$state->country_id . '_' . $state->name] = $state->id;
        }

        $this->cities = [];
        $allCities = City::select('id', 'name', 'state_id')->get();
        foreach ($allCities as $city) {
            $this->cities[$city->state_id . '_' . $city->name] = $city->id;
        }
    }

    public function model(array $row)
    {
        $row = array_map(function($val) { 
            return is_string($val) ? trim($val) : $val; 
        }, $row);

        $countryCode = $row['country'] ?? null;
        if (!$countryCode || !isset($this->countries[$countryCode])) {
            return null; // Skip if no valid country
        }
        $countryId = $this->countries[$countryCode];

        $stateName = $row['state'] ?? null;
        $stateKey = $countryId . '_' . $stateName;
        $stateId = $stateName && isset($this->states[$stateKey]) ? $this->states[$stateKey] : null;

        $cityName = $row['city'] ?? null;
        $cityKey = $stateId . '_' . $cityName;
        $cityId = $cityName && isset($this->cities[$cityKey]) ? $this->cities[$cityKey] : null;

        if (empty($row['postal_code'])) {
            return null;
        }

        return new Pincode([
            'postal_code'     => $row['postal_code'],
            'country_id'      => $countryId,
            'state_id'        => $stateId,
            'city_id'         => $cityId,
            'short_state'     => $row['short_state'] ?? null,
            'county'          => $row['county'] ?? null,
            'short_county'    => $row['short_county'] ?? null,
            'community'       => $row['community'] ?? null,
            'short_community' => $row['short_community'] ?? null,
            'latitude'        => $row['latitude'] ?? null,
            'longitude'       => $row['longitude'] ?? null,
            'accuracy'        => $row['accuracy'] ?? null,
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
