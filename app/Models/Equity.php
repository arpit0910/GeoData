<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equity extends Model
{
    use HasFactory;

    /**
     * Provider identifiers are internal-only. Admin controllers must opt in
     * with makeVisible() when they need to display these values.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'upstox_nse_instrument_key',
        'upstox_bse_instrument_key',
        'nse_exchange_token',
        'bse_exchange_token',
        'upstox_nse_metadata',
        'upstox_bse_metadata',
        'upstox_synced_at',
    ];

    protected $fillable = [
        'isin',
        'company_name',
        'short_name',
        'security_type',
        'company_profile',
        'nse_symbol',
        'bse_symbol',
        'industry',
        'market_cap',
        'market_cap_category',
        'face_value',
        'listing_date',
        'is_active',
        'series',
        'market_lot',
        'status',
        'sector',
        'basic_industry',
        'index_membership',
        'company_website',
        'cin',
        'nse_tick_size',
        'bse_tick_size',
        'nse_freeze_quantity',
        'bse_freeze_quantity',
        'qty_multiplier',
        'mtf_enabled',
        'mtf_bracket',
        'cas_eligible',
        'intraday_margin',
        'intraday_leverage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'face_value' => 'decimal:2',
        'listing_date' => 'date',
        'index_membership' => 'array',
        'market_lot' => 'integer',
        'qty_multiplier' => 'float',
        'mtf_enabled' => 'boolean',
        'mtf_bracket' => 'float',
        'cas_eligible' => 'boolean',
        'intraday_margin' => 'float',
        'intraday_leverage' => 'float',
        'nse_tick_size' => 'float',
        'bse_tick_size' => 'float',
        'nse_freeze_quantity' => 'float',
        'bse_freeze_quantity' => 'float',
        'upstox_nse_metadata' => 'array',
        'upstox_bse_metadata' => 'array',
        'upstox_synced_at' => 'datetime',
    ];

    public function prices()
    {
        return $this->hasMany(EquityPrice::class);
    }

    public function companyFundamentals()
    {
        return $this->hasMany(CompanyFundamental::class);
    }
}
