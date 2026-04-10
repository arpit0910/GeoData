<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pincode extends Model
{
    use HasFactory;

    protected $fillable = [
        'postal_code',
        'country_id',
        'state_id',
        'city_id',
        'short_state',
        'county',
        'short_county',
        'community',
        'short_community',
        'latitude',
        'longitude',
        'accuracy',
        'area',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
