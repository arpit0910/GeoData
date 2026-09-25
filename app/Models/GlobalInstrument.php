<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalInstrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'instrument_key',
        'segment',
        'name',
        'exchange',
        'country',
        'latency',
        'instrument_type',
        'trading_symbol',
        'start_time',
        'end_time',
        'week_days',
        'is_active',
        'provider_payload',
        'synced_at',
    ];

    /** Provider-specific identifiers and payloads stay internal. */
    protected $hidden = [
        'instrument_key',
        'provider_payload',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'provider_payload' => 'array',
        'synced_at' => 'datetime',
    ];
}
