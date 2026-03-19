<?php

namespace App\Models;


use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'account_type' => 'client',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'status',
        'company_website',
        'gst_number',
        'name',
        'is_admin',
        'company_name',
        'account_type',
        'client_key',
        'client_secret',
        'active_access_token',
        'token_expires_at',
        'plan_id',
        'available_credits'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'client_secret',
        'active_access_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if ($user->account_type === 'client') {
                $user->client_key = 'ck_' . \Illuminate\Support\Str::random(32);
                $user->client_secret = 'secret_' . \Illuminate\Support\Str::random(60);
            }
        });
    }
}
