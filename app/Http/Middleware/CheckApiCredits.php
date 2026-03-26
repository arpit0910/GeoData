<?php

namespace App\Http\Middleware;

use Closure;
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

        // Identify an active subscription possessing non-zero credits mapping properly to the user entity
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('available_credits', '>', 0)
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => false, 
                'message' => 'Payment Required. You do not have an active subscription or have exhausted your API credits.'
            ], 402);
        }

        // Process request to resolution inherently mapping exactly to our post-flight logic
        $response = $next($request);

        // Deduct natively only on total global resolution success explicitly yielding HTTP 200 OK
        $success = $response->status() == 200;

        if ($success) {
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
            'request_payload' => $request->all(),
            'credit_deducted' => $success, // Explicit boolean flags exactly recording the physical debit
        ]);

        return $response;
    }
}
