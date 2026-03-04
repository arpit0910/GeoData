<?php

namespace App\Imports;

use App\Models\State;
use App\Models\Country;
use App\Models\Timezone;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class StatesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
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
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $countryName = $row['country_name'] ?? null;
        $countryId = $this->countries[$countryName] ?? null;
        
        $timezoneName = $row['timezone'] ?? null;
        $timezoneId = null;
        
        if ($timezoneName && $countryId) {
            $timezoneId = $this->timezones[$countryId . '_' . $timezoneName] ?? null;
        }

        return new State([
            'name' => $row['name'] ?? null,
            'country_id' => $countryId ?? null,
            'timezone_id' => $timezoneId ?? null,
            'iso2' => $row['iso2'] ?? null,
            'iso3166_2' => $row['iso3166_2'] ?? null,
            'fips_code' => $row['fips_code'] ?? null,
            'type' => $row['type'] ?? null,
            'latitude' => $row['latitude'] ?? null,
            'longitude' => $row['longitude'] ?? null,
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
