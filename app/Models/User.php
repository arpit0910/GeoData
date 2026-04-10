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
        'address_line_1',
        'address_line_2',
        'country_id',
        'state_id',
        'city_id',
        'pincode',
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
        'timezone',
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
        'status' => 'integer',
        'is_admin' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function hasActiveSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class);
    }

    public function formatDate($date, $format = 'd-m-Y @ h:i A')
    {
        if (!$date) return 'N/A';
        
        $timezone = $this->timezone ?? config('app.timezone', 'UTC');
        
        return \Carbon\Carbon::parse($date)
            ->setTimezone($timezone)
            ->format($format);
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
