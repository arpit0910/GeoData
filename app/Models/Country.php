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
        'population',
        'gdp',
        'timezones',
        'max_mobile_digits',
        'international_prefix',
        'trunk_prefix',
        'income_level',
        'is_oecd',
        'is_eu',
        'driving_side',
        'measurement_system',
        'tax_system',
        'standard_tax_rate',
    ];

    protected $casts = [
        'is_oecd' => 'boolean',
        'is_eu' => 'boolean',
        'population' => 'decimal:2',
        'gdp' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'area_sq_km' => 'decimal:2',
    ];

     public function Region()
    {
        return $this->belongsTo(Region::class);
    }

     public function SubRegion()
    {
        return $this->belongsTo(SubRegion::class, 'subregion_id');
    }

    public function timezones()
    {
        return $this->hasMany(Timezone::class);
    }
}
