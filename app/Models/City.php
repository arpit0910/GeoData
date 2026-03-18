<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'state_id',
        'country_id',
        'latitude',
        'longitude',
        'type',
        'timezone_id',
        'wiki_data_id',
    ];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function State()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function Timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }
}
