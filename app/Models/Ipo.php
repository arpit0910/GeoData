<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'symbol', 'name', 'status', 'isin', 'issue_type', 'issue_size',
        'industry', 'minimum_price', 'maximum_price', 'bidding_start_date',
        'bidding_end_date', 'total_subscription', 'details', 'synced_at',
    ];

    protected $casts = [
        'issue_size' => 'float',
        'minimum_price' => 'float',
        'maximum_price' => 'float',
        'bidding_start_date' => 'date:Y-m-d',
        'bidding_end_date' => 'date:Y-m-d',
        'total_subscription' => 'float',
        'details' => 'array',
        'synced_at' => 'datetime',
    ];
}
