<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SanitizeProviderResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData($this->sanitize($response->getData(true)));
        }

        return $response;
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && str_contains(strtolower($key), 'upstox')) {
                    continue;
                }
                $sanitized[$key] = $this->sanitize($item);
            }

            return $sanitized;
        }

        return is_string($value)
            ? preg_replace('/upstox/i', 'market data provider', $value)
            : $value;
    }
}
