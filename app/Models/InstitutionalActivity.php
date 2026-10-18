<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionalActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'activity_at' => 'datetime',
        'buy_amount' => 'float',
        'sell_amount' => 'float',
        'oi_amount' => 'float',
        'buy_contracts' => 'integer',
        'sell_contracts' => 'integer',
        'oi_contracts' => 'integer',
        'total_long_contracts' => 'integer',
        'total_short_contracts' => 'integer',
        'total_call_long_contracts' => 'integer',
        'total_put_long_contracts' => 'integer',
        'total_call_short_contracts' => 'integer',
        'total_put_short_contracts' => 'integer',
        'synced_at' => 'datetime',
    ];
}
