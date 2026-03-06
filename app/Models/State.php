<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
        'iso2',
        'iso3166_2',
        'fips_code',
        'type',
        'latitude',
        'longitude',
        'timezone_id',
        'state_code',
        'wiki_data_id',
    ];

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }

    public function Timezone()
    {
        return $this->belongsTo(Timezone::class);
    }
}
