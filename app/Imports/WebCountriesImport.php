<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\Region;
use App\Models\SubRegion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebCountriesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowData = $row->map(function($value) {
                return is_string($value) ? trim($value) : $value;
            })->toArray();

            if (empty($rowData['name'])) {
                continue;
            }

            Country::updateOrCreate(
                ['name' => $rowData['name']],
                [
                    'iso3' => $rowData['iso3'] ?? null,
                    'iso2' => $rowData['iso2'] ?? null,
                    'numeric_code' => $rowData['numeric_code'] ?? null,
                    'phonecode' => $rowData['phonecode'] ?? null,
                    'capital' => $rowData['capital'] ?? null,
                    'currency' => $rowData['currency'] ?? null,
                    'currency_name' => $rowData['currency_name'] ?? null,
                    'currency_symbol' => $rowData['currency_symbol'] ?? null,
                    'tld' => $rowData['tld'] ?? null,
                    'native' => $rowData['native'] ?? null,
                    'region_id' => Region::where('name', $rowData['region'])->first()?->id ?? null,
                    'subregion_id' => SubRegion::where('name', $rowData['subregion'])->first()?->id ?? null,
                    'nationality' => $rowData['nationality'] ?? null,
                    'area_sq_km' => $rowData['area_sq_km'] ?? null,
                    'postal_code_format' => $rowData['postal_code_format'] ?? null,
                    'postal_code_regex' => $rowData['postal_code_regex'] ?? null,
                    'latitude' => $rowData['latitude'] ?? null,
                    'longitude' => $rowData['longitude'] ?? null,
                    'emoji' => $rowData['emoji'] ?? null,
                    'emojiU' => $rowData['emojiu'] ?? null,
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
