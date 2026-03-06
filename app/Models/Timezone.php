<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'zone_name',
        'gmt_offset',
        'gmt_offset_name',
        'abbreviation',
        'tz_name',
    ];

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }
}
