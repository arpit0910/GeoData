<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            // Sanctum Fallback: If auth middleware hasn't successfully resolved the user yet (e.g. Throttle runs first)
            if (!$user && $token = $request->bearerToken()) {
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($accessToken && $accessToken->tokenable) {
                    $user = $accessToken->tokenable;
                }
            }

            if ($user && $user->plan) {
                $plan = $user->plan;
                // Calculate actual cost
                $cost = (float)($plan->amount - ($plan->discount_amount ?? 0));

                if ($cost > 0) {
                    // Paid tier: 300 hits per minute (5 per second on average)
                    return Limit::perMinute(300)->by($user->id);
                }
            }

            // Free tier or unauthenticated: 60 hits per minute (1 per second on average)
            return Limit::perMinute(60)->by($user?->id ?: $request->ip());
        });
    }
}
