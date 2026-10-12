<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNudge extends Model
{
    use HasFactory;

    protected $fillable = [
        'equity_id', 'isin', 'exchange', 'type', 'rule', 'severity',
        'title', 'message', 'observed_price', 'metrics', 'trading_date',
        'observed_at', 'expires_at', 'fingerprint',
    ];

    protected $casts = [
        'metrics' => 'array',
        'trading_date' => 'date',
        'observed_at' => 'datetime',
        'expires_at' => 'datetime',
        'observed_price' => 'decimal:4',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }
}
