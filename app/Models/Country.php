<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso3',
        'iso2',
        'numeric_code',
        'phonecode',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'region_id',
        'subregion_id',
        'nationality',
        'area_sq_km',
        'postal_code_format',
        'postal_code_regex',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
        'wiki_data_id',
    ];

     public function Region()
    {
        return $this->belongsTo(Region::class);
    }

     public function SubRegion()
    {
        return $this->belongsTo(SubRegion::class, 'subregion_id');
    }
}
