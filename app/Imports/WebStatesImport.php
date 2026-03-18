<?php

namespace App\Imports;

use App\Models\State;
use App\Models\Country;
use App\Models\Timezone;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebStatesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $countries;
    private $timezones;

    public function __construct()
    {
        // Load countries to avoid excessive DB queries
        $this->countries = Country::pluck('id', 'name')->toArray();
        
        // Load timezones mapped by country_id AND zone_name
        $timezonesData = Timezone::select('id', 'country_id', 'zone_name')->get();
        $this->timezones = [];
        foreach ($timezonesData as $tz) {
            $this->timezones[$tz->country_id . '_' . $tz->zone_name] = $tz->id;
        }
    }

    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowData = $row->map(function($value) {
                return is_string($value) ? trim($value) : $value;
            })->toArray();

            $countryName = $rowData['country_name'] ?? null;
            $countryId = $this->countries[$countryName] ?? null;
            
            $timezoneName = $rowData['timezone'] ?? null;
            $timezoneId = null;
            
            if ($timezoneName && $countryId) {
                $timezoneId = $this->timezones[$countryId . '_' . $timezoneName] ?? null;
            }

            if (empty($rowData['name']) || empty($countryId)) {
                continue; // Cannot update or create without state name and country_id
            }

            State::updateOrCreate(
                [
                    'name' => $rowData['name'],
                    'country_id' => $countryId
                ],
                [
                    'timezone_id' => $timezoneId ?? null,
                    'iso2' => $rowData['iso2'] ?? null,
                    'iso3166_2' => $rowData['iso3166_2'] ?? null,
                    'fips_code' => $rowData['fips_code'] ?? null,
                    'type' => $rowData['type'] ?? null,
                    'latitude' => $rowData['latitude'] ?? null,
                    'longitude' => $rowData['longitude'] ?? null,
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
