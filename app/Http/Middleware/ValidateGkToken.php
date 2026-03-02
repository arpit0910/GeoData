<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateGkToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return sendResponse(null, 'Unauthenticated', 401);
        }

        // Fast query using indexed column
        $user = User::where('active_access_token', $token)
            ->where('token_expires_at', '>', now())
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return sendResponse(null, 'Invalid or expired token', 401);
        }

        // Manually authenticate the user for the request
        Auth::setUser($user);

        return $next($request);
    }
}
