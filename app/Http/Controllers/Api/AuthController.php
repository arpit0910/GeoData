<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'client_key' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $user = User::where('client_key', $request->client_key)->first();

        // Evaluate client_secret perfectly (stored without hashing during onboarding logic implicitly natively inside Laravel boot model)
        if (! $user || $user->client_secret !== $request->client_secret) {
            return sendResponse(null, 'Invalid API credentials', 401);
        }

        if ($user->status === 0) {
            return sendResponse(null, 'Account is inactive. Please contact support.', 403);
        }

        // Optional: clear out preceding outdated tokens for this specific device flow
        $user->tokens()->where('name', 'setugeo-auth-token')->delete();

        // Issue formal Sanctum token targeting user context mapping perfectly to `request()->user()`
        $tokenResult = $user->createToken('setugeo-auth-token');

        return sendResponse([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Token generated successfully', 200);
    }
}
