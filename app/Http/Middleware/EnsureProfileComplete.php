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
            $user = Auth::user();
            if (empty($user->company_name) || empty($user->phone)) {
                return redirect()->route('profile.complete');
            }
        }
        return $next($request);
    }
}
