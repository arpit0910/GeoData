<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_admin) {
            // Skip the check if we're already on the profile completion routes
            if (!$request->routeIs('profile.complete') && !$request->routeIs('profile.complete.post')) {
                $user = Auth::user();
                if (empty($user->company_name) || empty($user->phone)) {
                    return redirect()->route('profile.complete');
                }
            }
        }
        return $next($request);
    }
}
