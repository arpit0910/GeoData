<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeCalendarEvent extends Model
{
    use HasFactory;

    public const EXCHANGE_BOTH = 'NSE_BSE';
    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_MUHURAT = 'muhurat';
    public const TYPE_WEEKEND = 'weekend';

    protected $fillable = [
        'exchange',
        'segment',
        'event_date',
        'name',
        'event_type',
        'session_start',
        'session_end',
        'source',
        'source_url',
        'source_payload',
        'is_manually_overridden',
        'synced_at',
    ];

    protected $casts = [
        'event_date' => 'date:Y-m-d',
        'source_payload' => 'array',
        'is_manually_overridden' => 'boolean',
        'synced_at' => 'datetime',
    ];
}
