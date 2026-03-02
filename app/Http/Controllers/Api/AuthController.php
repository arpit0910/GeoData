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

        if (! $user || ! Hash::check($request->client_secret, $user->client_secret)) {
            return sendResponse(null, 'Invalid credentials', 401);
        }

        if ($user->status !== 'active') {
            return sendResponse(null, 'Account is inactive', 403);
        }

        $token = Str::random(80);

        $user->forceFill([
            'active_access_token' => $token, // You might want to hash this in production
            'token_expires_at' => now()->addDays(30),
        ])->save();

        return sendResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $user->token_expires_at,
        ], 'Token generated successfully', 200);
    }
}
