<?php

namespace App\Services;

use App\Models\UpstoxAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UpstoxTokenManager
{
    public function current(): ?UpstoxAccessToken
    {
        return UpstoxAccessToken::query()
            ->where('status', 'active')
            ->whereNotNull('access_token')
            ->where('expires_at', '>', now()->addMinute())
            ->latest('issued_at')
            ->first();
    }

    public function accessToken(): string
    {
        $token = $this->current()?->access_token
            ?: trim((string) config('market_data.upstox.access_token'));

        if ($token === '') {
            throw new RuntimeException('No active Upstox token is available. Approve the pending Upstox token request.');
        }

        return $token;
    }

    /** @return array{requested: bool, authorization_expires_at: ?Carbon, notifier_url: ?string} */
    public function requestRenewal(bool $force = false): array
    {
        $clientId = trim((string) config('market_data.upstox.client_id'));
        $clientSecret = trim((string) config('market_data.upstox.client_secret'));
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('UPSTOX_CLIENT_ID and UPSTOX_CLIENT_SECRET must be configured to renew tokens.');
        }

        if (! $force) {
            $pending = UpstoxAccessToken::query()
                ->where('client_id', $clientId)
                ->where('status', 'pending')
                ->latest()
                ->first();
            if ($pending?->authorization_expires_at?->isFuture()) {
                return [
                    'requested' => false,
                    'authorization_expires_at' => $pending->authorization_expires_at,
                    'notifier_url' => data_get($pending->metadata, 'notifier_url'),
                ];
            }
        }

        $url = rtrim((string) config('market_data.upstox.token_request_url'), '/').'/'.rawurlencode($clientId);
        $response = Http::acceptJson()
            ->withOptions(['verify' => config('market_data.ca_bundle') ?: false])
            ->connectTimeout(10)
            ->timeout(30)
            ->post($url, ['client_secret' => $clientSecret]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            $message = data_get($response->json(), 'errors.0.message') ?: $response->body();
            throw new RuntimeException('Upstox token renewal request failed with HTTP '.$response->status().($message ? ': '.$message : '.'));
        }

        $authorizationExpiry = $this->fromMilliseconds($response->json('data.authorization_expiry'));
        $notifierUrl = $response->json('data.notifier_url');
        UpstoxAccessToken::create([
            'client_id' => $clientId,
            'status' => 'pending',
            'renewal_requested_at' => now()->utc(),
            'authorization_expires_at' => $authorizationExpiry,
            'metadata' => ['notifier_url' => $notifierUrl],
        ]);

        return [
            'requested' => true,
            'authorization_expires_at' => $authorizationExpiry,
            'notifier_url' => $notifierUrl,
        ];
    }

    public function storeNotifierToken(array $payload): UpstoxAccessToken
    {
        foreach (['client_id', 'access_token', 'expires_at', 'issued_at', 'message_type'] as $field) {
            if (empty($payload[$field])) {
                throw new RuntimeException("Missing Upstox notifier field: {$field}.");
            }
        }
        $clientId = trim((string) config('market_data.upstox.client_id'));
        if ($clientId === '' || ! hash_equals($clientId, (string) $payload['client_id'])) {
            throw new RuntimeException('The Upstox notifier client ID is invalid.');
        }
        if ($payload['message_type'] !== 'access_token') {
            throw new RuntimeException('The Upstox notifier message type is invalid.');
        }

        return DB::transaction(function () use ($payload, $clientId) {
            UpstoxAccessToken::where('status', 'active')->update(['status' => 'expired']);
            $record = UpstoxAccessToken::query()
                ->where('client_id', $clientId)
                ->where('status', 'pending')
                ->latest()
                ->lockForUpdate()
                ->first() ?: new UpstoxAccessToken(['client_id' => $clientId]);
            $record->fill([
                'access_token' => (string) $payload['access_token'],
                'token_type' => (string) ($payload['token_type'] ?? 'Bearer'),
                'upstox_user_id' => isset($payload['user_id']) ? (string) $payload['user_id'] : null,
                'status' => 'active',
                'issued_at' => $this->fromMilliseconds($payload['issued_at']),
                'expires_at' => $this->fromMilliseconds($payload['expires_at']),
                'metadata' => ['message_type' => 'access_token'],
            ]);
            $record->save();

            return $record;
        });
    }

    private function fromMilliseconds(mixed $value): Carbon
    {
        if (! is_numeric($value)) {
            throw new RuntimeException('Upstox returned an invalid token timestamp.');
        }

        return Carbon::createFromTimestampMsUTC((int) $value)
            ->setTimezone((string) config('app.timezone', 'UTC'));
    }
}
