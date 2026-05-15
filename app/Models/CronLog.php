<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronLog extends Model
{
    protected $fillable = ['title', 'ip', 'status', 'ran_at'];

    protected $casts = [
        'ran_at' => 'datetime',
        'status' => 'boolean',
    ];
}
