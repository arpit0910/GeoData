<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_hits_limit',
        'amount',
        'discount_amount',
        'status',
        'billing_cycle',
        'terms',
        'benefits',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];
}
