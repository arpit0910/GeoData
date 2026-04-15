<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexPrice extends Model
{
    use HasFactory;

    protected $table = 'indices_prices';

    protected $fillable = [
        'index_code',
        'traded_date',
        'open',
        'high',
        'low',
        'close',
        'prev_close',
        'change_percent',
        'volume',
        'turnover',
        'pe_ratio',
        'pb_ratio',
        'div_yield',
        'chg_1d',
        'chg_3d',
        'chg_7d',
        'chg_1m',
        'chg_3m',
        'chg_6m',
        'chg_9m',
        'chg_1y',
        'chg_3y',
        'gap_pct',
        'intraday_chg_pct',
        'range_pct',
    ];

    protected $casts = [
        'traded_date' => 'date',
        'open'        => 'decimal:2',
        'high'        => 'decimal:2',
        'low'         => 'decimal:2',
        'close'       => 'decimal:2',
        'prev_close'  => 'decimal:2',
        'change_percent' => 'decimal:4',
        'volume'      => 'decimal:2',
        'turnover'    => 'decimal:2',
        'pe_ratio'    => 'decimal:2',
        'pb_ratio'    => 'decimal:2',
        'div_yield'   => 'decimal:2',
        'chg_1d'      => 'decimal:2',
        'chg_3d'      => 'decimal:2',
        'chg_7d'      => 'decimal:2',
        'chg_1m'      => 'decimal:2',
        'chg_3m'      => 'decimal:2',
        'chg_6m'      => 'decimal:2',
        'chg_9m'      => 'decimal:2',
        'chg_1y'      => 'decimal:2',
        'chg_3y'      => 'decimal:2',
        'gap_pct'     => 'decimal:2',
        'intraday_chg_pct' => 'decimal:2',
        'range_pct'   => 'decimal:2',
    ];

    public function index()
    {
        return $this->belongsTo(Index::class, 'index_code', 'index_code');
    }
}
