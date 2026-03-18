<?php

namespace App\Imports;

use App\Models\Timezone;
use App\Models\Country;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebTimezonesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $countryName = trim($row['name'] ?? '');
            $timezonesString = trim($row['timezones'] ?? '');

            if (empty($countryName) || empty($timezonesString)) {
                continue;
            }

            // Find the corresponding Country
            $country = Country::where('name', $countryName)->first();

            if (!$country) {
                continue;
            }

            // Clean the timezones string to make it valid JSON
            // 1. Add double quotes around keys (e.g., zoneName: -> "zoneName":)
            $cleanedString = preg_replace('/([{,])\s*([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $timezonesString);
            
            // 2. Replace single quotes used for string values with double quotes
            $cleanedString = str_replace("'", '"', $cleanedString);

            // Now decode the JSON
            $timezonesArray = json_decode($cleanedString, true);

            if (is_array($timezonesArray)) {
                foreach ($timezonesArray as $tz) {
                    Timezone::updateOrCreate(
                        [
                            'country_id' => $country->id,
                            'zone_name'  => isset($tz['zoneName']) ? trim($tz['zoneName']) : null,
                        ],
                        [
                            'gmt_offset'      => isset($tz['gmtOffset']) ? trim((string)$tz['gmtOffset']) : null,
                            'gmt_offset_name' => isset($tz['gmtOffsetName']) ? trim($tz['gmtOffsetName']) : null,
                            'abbreviation'    => isset($tz['abbreviation']) ? trim($tz['abbreviation']) : null,
                            'tz_name'         => isset($tz['tzName']) ? trim($tz['tzName']) : null,
                        ]
                    );
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
