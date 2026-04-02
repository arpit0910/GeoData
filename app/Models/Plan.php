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

    /**
     * Removed auto-sync on creation to prioritize local model consistency.
     * Plans can be manually synced via the UI or explicitly in the controller.
     */

    public function syncWithRazorpay()
    {
        $amount = (float)($this->amount - ($this->discount_amount ?? 0));
        
        $key = env('RAZORPAY_KEY');
        $secret = env('RAZORPAY_SECRET');

        if (empty($key) || empty($secret)) {
            throw new \Exception('Razorpay credentials are missing! Please check your .env file.');
        }

        if ($amount <= 0) {
            throw new \Exception('Free plans do not need to be synced with Razorpay.');
        }

        if (!in_array($this->billing_cycle, ['monthly', 'yearly'])) {
             throw new \Exception('Razorpay subscriptions only support monthly or yearly cycles.');
        }

        try {
            $api = new \Razorpay\Api\Api($key, $secret);
            $planData = [
                'period' => $this->billing_cycle,
                'interval' => 1,
                'item' => [
                    'name' => $this->name,
                    'amount' => (int)($amount * 100), // in paise
                    'currency' => 'INR',
                    'description' => substr($this->terms ?? $this->name . ' Subscription', 0, 255)
                ]
            ];
            
            $razorpayPlan = $api->plan->create($planData);
            $this->gateway_product_id = $razorpayPlan->id;
            $this->save();
            
            return $razorpayPlan->id;
        } catch (\Exception $e) {
            \Log::error('Razorpay Sync Failed for Plan ID ' . $this->id . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
