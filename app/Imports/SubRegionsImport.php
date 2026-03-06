<?php

namespace App\Imports;

use App\Models\SubRegion;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class SubRegionsImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
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

        return new SubRegion([
            'name' => $row['name'] ?? null,
            'region_id' => $row['region_id'] ?? null,
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
