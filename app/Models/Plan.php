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
        'gateway_product_id',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    protected static function booted()
    {
        static::creating(function ($plan) {
            $amount = $plan->amount - ($plan->discount_amount ?? 0);
            
            if ($amount > 0 && empty($plan->gateway_product_id) && in_array($plan->billing_cycle, ['monthly', 'yearly'])) {
                try {
                    $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY', 'rzp_test_dummy'), env('RAZORPAY_SECRET', 'dummy_secret'));
                    
                    $planData = [
                        'period' => $plan->billing_cycle,
                        'interval' => 1,
                        'item' => [
                            'name' => $plan->name,
                            'amount' => $amount * 100, // in paise
                            'currency' => 'INR',
                            'description' => substr($plan->terms ?? $plan->name . ' Subscription', 0, 255)
                        ]
                    ];
                    
                    $razorpayPlan = $api->plan->create($planData);
                    $plan->gateway_product_id = $razorpayPlan->id;
                } catch (\Exception $e) {
                    \Log::error('Razorpay Plan Creation Failed: ' . $e->getMessage());
                    if (app()->runningInConsole()) {
                        throw $e;
                    }
                }
            }
        });
    }
}
