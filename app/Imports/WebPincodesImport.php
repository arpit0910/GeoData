<?php

namespace App\Imports;

use App\Models\Pincode;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebPincodesImport implements ToCollection, WithHeadingRow, WithChunkReading
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

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowData = $row->map(function($val) { 
                return is_string($val) ? trim($val) : $val; 
            })->toArray();

            $countryCode = $rowData['country'] ?? null;
            if (!$countryCode || !isset($this->countries[$countryCode])) {
                continue; // Skip if no valid country
            }
            $countryId = $this->countries[$countryCode];

            $stateName = $rowData['state'] ?? null;
            $stateKey = $countryId . '_' . $stateName;
            $stateId = $stateName && isset($this->states[$stateKey]) ? $this->states[$stateKey] : null;

            $cityName = $rowData['city'] ?? null;
            $cityKey = $stateId . '_' . $cityName;
            $cityId = $cityName && isset($this->cities[$cityKey]) ? $this->cities[$cityKey] : null;

            if (empty($rowData['postal_code'])) {
                continue;
            }

            Pincode::updateOrCreate(
                [
                    'postal_code' => $rowData['postal_code'],
                    'country_id'  => $countryId,
                ],
                [
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
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
