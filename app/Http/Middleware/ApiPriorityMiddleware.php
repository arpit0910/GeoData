<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiPriorityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Sanctum Fallback: Manual resolution if auth middleware hasn't run yet
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        // Identify if the user is on a paid tier
        $isPaid = false;
        if ($user && $user->plan) {
            $cost = $user->plan->amount - ($user->plan->discount_amount ?? 0);
            if ($cost > 0) {
                $isPaid = true;
            }
        }

        $appliedDelay = false;

        if ($isPaid) {
            // High Priority Account: Signal activity to others and process immediately
            \Illuminate\Support\Facades\Cache::increment('api:paid_requests_active');
            
            try {
                $response = $next($request);
            } finally {
                // Ensure we always decrement, even on error
                \Illuminate\Support\Facades\Cache::decrement('api:paid_requests_active');
                
                // Safety cleanup: Ensure counter doesn't stay negative if something went wrong
                if ((int)\Illuminate\Support\Facades\Cache::get('api:paid_requests_active') < 0) {
                    \Illuminate\Support\Facades\Cache::put('api:paid_requests_active', 0, 60);
                }
            }
        } else {
            // Normal Priority Account: Check for paid user competition
            $activePaidUsers = (int)\Illuminate\Support\Facades\Cache::get('api:paid_requests_active', 0);
            
            // Allow testing the delay logic if explicitly requested via header in testing environment
            $shouldDelay = !app()->environment('testing') || $request->header('X-Test-Priority');

            if ($activePaidUsers > 0 && $shouldDelay) {
                // Prioritization: Only delay if subscribers are actively using the system
                usleep(250000); 
                $appliedDelay = true;
            }
            
            $response = $next($request);
        }

        // Append priority metadata to headers
        $response->headers->set('X-Api-Priority', $isPaid ? 'High' : 'Normal');
        $response->headers->set('X-Api-Tier', $isPaid ? 'Paid' : 'Free');
        $response->headers->set('X-Response-Time-Priority', $appliedDelay ? 'Scheduled-Delay' : 'Immediate');

        return $response;
    }
}
