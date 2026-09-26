<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpstoxAccessToken extends Model
{
    protected $fillable = [
        'client_id',
        'access_token',
        'token_type',
        'upstox_user_id',
        'status',
        'issued_at',
        'expires_at',
        'renewal_requested_at',
        'authorization_expires_at',
        'metadata',
    ];

    protected $hidden = ['access_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_requested_at' => 'datetime',
        'authorization_expires_at' => 'datetime',
        'metadata' => 'array',
    ];
}
