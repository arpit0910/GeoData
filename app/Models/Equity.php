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
        'face_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'face_value' => 'decimal:2',
    ];

    public function prices()
    {
        return $this->hasMany(EquityPrice::class);
    }
}
