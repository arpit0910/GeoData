<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSubscribed
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
        if (Auth::check() && !Auth::user()->is_admin) {
            $user = Auth::user();
            
            // If status is false (0) and not null, don't allow access (should have been blocked by login too)
            if ($user->status === 0) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Your account is inactive. Please contact support.');
            }

            // Whitelisted routes that don't require an active subscription
            $allowedRoutes = [
                'pricing', 
                'subscription.pricing',
                'pricing.order', 
                'pricing.verify', 
                'pricing.validate-coupon',
                'logout', 
                'profile.complete', 
                'profile.complete.post',
                'api.pincode.lookup',
                'support.index',
                'support.store',
                'support.sub-categories'
            ];
            
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if ($currentRoute && !in_array($currentRoute, $allowedRoutes)) {
                // User must have status 1 (active) and an active subscription to access other routes
                if ($user->status != 1 || !$user->hasActiveSubscription()) {
                    return redirect()->route('subscription.pricing')->with('error', 'Please subscribe to a plan to access the dashboard.');
                }
            }
        }

        return $next($request);
    }
}
