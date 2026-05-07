<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfNavHistory extends Model
{
    protected $table      = 'mutual_fund_prices';
    public    $timestamps = false;

    protected $fillable = [
        'mf_id',
        'isin',
        'nav_date',
        'nav',
        'chg_1d',
        'val_1d',
        'chg_3d',
        'val_3d',
        'chg_7d',
        'val_7d',
        'chg_1m',
        'val_1m',
        'chg_3m',
        'val_3m',
        'chg_6m',
        'val_6m',
        'chg_9m',
        'val_9m',
        'chg_1y',
        'val_1y',
        'chg_3y',
        'val_3y',
    ];

    protected $casts = [
        'nav_date' => 'date',
        'nav'      => 'decimal:4',
        'chg_1d'   => 'decimal:4',
        'val_1d' => 'decimal:4',
        'chg_3d'   => 'decimal:4',
        'val_3d' => 'decimal:4',
        'chg_7d'   => 'decimal:4',
        'val_7d' => 'decimal:4',
        'chg_1m'   => 'decimal:4',
        'val_1m' => 'decimal:4',
        'chg_3m'   => 'decimal:4',
        'val_3m' => 'decimal:4',
        'chg_6m'   => 'decimal:4',
        'val_6m' => 'decimal:4',
        'chg_9m'   => 'decimal:4',
        'val_9m' => 'decimal:4',
        'chg_1y'   => 'decimal:4',
        'val_1y' => 'decimal:4',
        'chg_3y'   => 'decimal:4',
        'val_3y' => 'decimal:4',
    ];

    public function scheme()
    {
        return $this->belongsTo(MfMaster::class, 'isin', 'isin');
    }
}
