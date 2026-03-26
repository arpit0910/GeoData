<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'endpoint',
        'method',
        'status_code',
        'ip_address',
        'request_payload',
        'credit_deducted',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'credit_deducted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
