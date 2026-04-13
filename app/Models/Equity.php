<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equity extends Model
{
    use HasFactory;

    protected $fillable = [
        'isin',
        'company_name',
        'nse_symbol',
        'bse_symbol',
        'industry',
        'market_cap',
        'market_cap_category',
        'face_value',
        'listing_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'face_value' => 'decimal:2',
        'listing_date' => 'date',
    ];

    public function prices()
    {
        return $this->hasMany(EquityPrice::class);
    }
}
