<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketNudge extends Model
{
    protected $fillable = [
        'source', 'source_key', 'rule', 'severity', 'title', 'message',
        'metrics', 'event_at', 'fingerprint',
    ];

    protected $casts = [
        'metrics' => 'array',
        'event_at' => 'datetime',
    ];
}
