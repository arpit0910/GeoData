<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquityPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'equity_id',
        'isin',
        'traded_date',
        'nse_open',
        'nse_high',
        'nse_low',
        'nse_close',
        'nse_last',
        'nse_prev_close',
        'nse_volume',
        'bse_open',
        'bse_high',
        'bse_low',
        'bse_close',
        'bse_last',
        'bse_prev_close',
        'bse_volume',
        'nse_chg_1d', 'nse_chg_3d', 'nse_chg_7d', 'nse_chg_1m',
        'bse_chg_1d', 'bse_chg_3d', 'bse_chg_7d', 'bse_chg_1m',
        'spread',
    ];

    protected $casts = [
        'traded_date' => 'date',
        'nse_open' => 'decimal:2',
        'nse_high' => 'decimal:2',
        'nse_low' => 'decimal:2',
        'nse_close' => 'decimal:2',
        'nse_prev_close' => 'decimal:2',
        'bse_open' => 'decimal:2',
        'bse_high' => 'decimal:2',
        'bse_low' => 'decimal:2',
        'bse_close' => 'decimal:2',
        'bse_prev_close' => 'decimal:2',
        'spread' => 'decimal:2',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }
}
