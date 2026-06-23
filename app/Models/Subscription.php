<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'razorpay_subscription_id',
        'amount_paid',
        'status',
        'expires_at',
        'total_credits',
        'used_credits',
        'available_credits',
        'coupon_id',
        'discount_amount',
        'remaining_discount_cycles',
        'last_credit_refresh',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_credit_refresh' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function hasFeature(string $featureKey): bool
    {
        return $this->plan?->hasFeature($featureKey) ?? false;
    }
}
