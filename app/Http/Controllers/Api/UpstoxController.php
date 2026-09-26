<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UpstoxTokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UpstoxController extends Controller
{
    /**
     * Receive the OAuth redirect from Upstox.
     */
    public function callback(Request $request): JsonResponse
    {
        Log::channel('upstox')->info('Upstox OAuth callback received.', [
            'has_code' => $request->filled('code'),
            'has_state' => $request->filled('state'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Market data authorization received.',
        ]);
    }

    public function notifier(Request $request, string $secret, UpstoxTokenManager $tokens): JsonResponse
    {
        try {
            $configuredSecret = trim((string) config('market_data.upstox.notifier_secret'));
            if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
                return response()->json(['success' => false, 'message' => 'Invalid notifier endpoint.'], 404);
            }
            $token = $tokens->storeNotifierToken($request->all());
            Log::channel('upstox')->info('Upstox access token stored.', [
                'token_id' => $token->id,
                'client_id' => $token->client_id,
                'expires_at' => $token->expires_at?->toIso8601String(),
            ]);

            return response()->json(['success' => true, 'message' => 'Access token stored.']);
        } catch (RuntimeException $exception) {
            Log::channel('upstox')->warning('Invalid Upstox notifier payload.', [
                'message' => $exception->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}

