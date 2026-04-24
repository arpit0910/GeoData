<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use App\Models\ApiLog;

class CheckApiCredits
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Identify an active subscription
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => false, 
                'message' => 'Payment Required. You do not have an active subscription.'
            ], 402);
        }

        $plan = $subscription->plan;
        
        // 1. Handle Unlimited (api_hits_limit is null)
        $isUnlimited = is_null($plan->api_hits_limit);

        // 2. Yearly Refresh Logic (Refresh credits every month)
        if (!$isUnlimited && $plan->billing_cycle === 'yearly') {
            $lastRefresh = $subscription->last_credit_refresh ?? $subscription->created_at;
            
            // If at least one full month has passed since last refresh (tenure-based), reset the credits
            if (now()->greaterThanOrEqualTo($lastRefresh->copy()->addMonth())) {
                $subscription->update([
                    'available_credits' => $plan->api_hits_limit,
                    'last_credit_refresh' => now()
                ]);
                $subscription->refresh(); // Load fresh credits from DB into memory
            }
        }

        // 3. Exhaustion Check
        if (!$isUnlimited && $subscription->available_credits <= 0) {
            return response()->json([
                'status' => false, 
                'message' => 'API credits exhausted. Please upgrade your plan or wait for the next month for credit refresh.'
            ], 402);
        }

        // Process request
        $response = $next($request);

        // Deduct only on success (200 OK) and if NOT unlimited
        $success = $response->status() == 200;

        if ($success && !$isUnlimited) {
            $subscription->decrement('available_credits');
            $subscription->increment('used_credits');
        }

        // Trace and record explicitly keeping permanent historical snapshots
        ApiLog::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->status(),
            'ip_address' => $request->ip(),
            'request_payload' => $this->sanitizePayload($request->all()),
            'credit_deducted' => $success, // Explicit boolean flags exactly recording the physical debit
        ]);

        return $response;
    }

    private function sanitizePayload(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'original_name' => $value->getClientOriginalName(),
                'mime_type' => $value->getMimeType(),
                'size' => $value->getSize(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->sanitizePayload($item), $value);
        }

        return $value;
    }
}
