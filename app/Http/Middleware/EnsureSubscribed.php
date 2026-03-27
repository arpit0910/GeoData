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
            
            // Whitelisted routes that don't require an active subscription
            $allowedRoutes = [
                'pricing', 
                'pricing.order', 
                'pricing.verify', 
                'pricing.validate-coupon',
                'logout', 
                'profile.complete', 
                'profile.complete.post'
            ];
            
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if ($currentRoute && !in_array($currentRoute, $allowedRoutes)) {
                if (!$user->hasActiveSubscription()) {
                    return redirect()->route('pricing')->with('error', 'Please subscribe to a plan to access the dashboard.');
                }
            }
        }

        return $next($request);
    }
}
