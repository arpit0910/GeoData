<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpstoxController extends Controller
{
    /**
     * Receive the OAuth redirect from Upstox.
     */
    public function callback(Request $request): JsonResponse
    {
        Log::channel('upstox')->info('Upstox OAuth callback received.', [
            'query' => $request->query(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Market data authorization received.',
        ]);
    }
}

