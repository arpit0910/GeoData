<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'max_redemptions',
        'used_count',
        'single_use_per_user',
        'apply_to_cycles',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'single_use_per_user' => 'boolean',
        'status' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isValidForPlan($planId)
    {
        if (!$this->plan_id) {
            return true;
        }
        return $this->plan_id == $planId;
    }

    public function isExpired()
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    public function hasRedemptionsLeft()
    {
        if (!$this->max_redemptions) {
            return true;
        }
        return $this->used_count < $this->max_redemptions;
    }
}
