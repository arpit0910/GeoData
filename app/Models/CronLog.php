<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronLog extends Model
{
    protected $fillable = [
        'title',
        'ip',
        'source',
        'status',
        'exit_code',
        'started_at',
        'finished_at',
        'ran_at',
    ];

    protected $casts = [
        'ran_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'status' => 'boolean',
    ];
}
