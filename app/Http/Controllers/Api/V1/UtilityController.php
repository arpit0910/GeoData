<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Plan;
use Illuminate\Http\Request;

class UtilityController extends Controller
{
    /**
     * List all active FAQs.
     */
    public function faqs(Request $request)
    {
        $faqs = Faq::where('status', 1)
            ->when($request->query('visibility'), function($q) use ($request) {
                return $q->where('visibility', $request->query('visibility'));
            })
            ->orderBy('order')
            ->get(['id', 'question', 'answer', 'category', 'visibility']);

        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    }

    /**
     * List all active subscription plans.
     */
    public function plans()
    {
        $plans = Plan::where('status', 'active')
            ->orderBy('monthly_price')
            ->get(['id', 'name', 'description', 'monthly_price', 'yearly_price', 'lifetime_price', 'credits_per_month', 'benefits']);

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Get detailed context-aware profile information without consuming credits.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->toIso8601String(),
                ],
                'subscription' => $subscription ? [
                    'plan_name' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'total_credits' => $subscription->total_credits,
                    'used_credits' => $subscription->used_credits,
                    'available_credits' => $subscription->available_credits,
                    'expires_at' => $subscription->expires_at->toIso8601String(),
                    'is_expired' => $subscription->expires_at->isPast(),
                ] : null,
                'integration' => [
                    'client_key' => $user->client_key,
                    'client_secret' => $user->client_secret, // Return carefully as per API context security
                ]
            ]
        ]);
    }
}
