<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'currency',
        'usd_conversion_rate',
        'inr_conversion_rate',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
