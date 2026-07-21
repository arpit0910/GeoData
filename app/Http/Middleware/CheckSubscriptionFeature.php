<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionAccessService;
use Closure;
use Illuminate\Http\Request;

class CheckSubscriptionFeature
{
    public function __construct(
        protected SubscriptionAccessService $subscriptionAccessService
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $module = null)
    {
        $user = $request->user();

        if (!$user) {
            return sendResponse(null, 'Unauthenticated.', 401);
        }

        if ($user->is_admin && $request->headers->get('X-Admin-Api-Tester') === '1') {
            return $next($request);
        }

        $result = $this->subscriptionAccessService->resolveAccess($user, $module);

        if ($result['allowed']) {
            return $next($request);
        }

        $payload = [
            'success' => false,
            'message' => $result['message'],
            'data' => null,
        ];

        if (!is_null($module)) {
            $payload['required_module'] = $module;
        }

        return response()->json($payload, $result['status']);
    }
}
