<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\Region;
use App\Models\SubRegion;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class CountriesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $row = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $row);

        return new Country([
            'name' => $row['name'] ?? null,
            'iso3' => $row['iso3'] ?? null,
            'iso2' => $row['iso2'] ?? null,
            'numeric_code' => $row['numeric_code'] ?? null,
            'phonecode' => $row['phonecode'] ?? null,
            'capital' => $row['capital'] ?? null,
            'currency' => $row['currency'] ?? null,
            'currency_name' => $row['currency_name'] ?? null,
            'currency_symbol' => $row['currency_symbol'] ?? null,
            'tld' => $row['tld'] ?? null,
            'native' => $row['native'] ?? null,
            'region_id' => Region::where('name', $row['region'])->first()?->id ?? null,
            'subregion_id' => SubRegion::where('name', $row['subregion'])->first()?->id ?? null,
            'nationality' => $row['nationality'] ?? null,
            'area_sq_km' => $row['area_sq_km'] ?? null,
            'postal_code_format' => $row['postal_code_format'] ?? null,
            'postal_code_regex' => $row['postal_code_regex'] ?? null,
            'latitude' => $row['latitude'] ?? null,
            'longitude' => $row['longitude'] ?? null,
            'emoji' => $row['emoji'] ?? null,
            'emojiU' => $row['emojiu'] ?? null,
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
