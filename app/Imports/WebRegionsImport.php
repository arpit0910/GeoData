<?php

namespace App\Imports;

use App\Models\Region;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebRegionsImport implements ToCollection, WithHeadingRow, WithChunkReading
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

            Region::updateOrCreate(
                ['name' => $rowData['name']],
                [
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
