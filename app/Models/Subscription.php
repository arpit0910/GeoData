<?php

namespace App\Models;

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
}
