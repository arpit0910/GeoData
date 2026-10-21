<?php

namespace App\Services;

use App\Models\UpstoxAccessToken;
use App\Support\TlsCaBundle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UpstoxTokenManager
{
    public function current(): ?UpstoxAccessToken
    {
        $lifetimeYears = $this->lifetimeYears();
        $clientId = trim((string) config('market_data.upstox.client_id'));

        return UpstoxAccessToken::query()
            ->when($clientId !== '', fn ($query) => $query->where('client_id', $clientId))
            ->where('status', 'active')
            ->whereNotNull('access_token')
            ->where('expires_at', '>', now()->addMinute())
            ->where('issued_at', '>', now()->subYears($lifetimeYears)->addMinute())
            ->latest('issued_at')
            ->first();
    }

    public function accessToken(): string
    {
        $analyticsToken = trim((string) config('market_data.upstox.analytics_token'));
        if ($analyticsToken !== '') {
            return $this->rejectExpiredJwt($analyticsToken, 'UPSTOX_ANALYTICS_TOKEN');
        }

        if ($current = $this->current()) {
            return $current->access_token;
        }

        $clientId = trim((string) config('market_data.upstox.client_id'));
        $pending = UpstoxAccessToken::query()
            ->when($clientId !== '', fn ($query) => $query->where('client_id', $clientId))
            ->where('authorization_expires_at', '>', now())
            ->exists();

        if ($pending) {
            throw new RuntimeException('No active Upstox token is available. Approve the pending Upstox token request.');
        }

        $token = trim((string) config('market_data.upstox.access_token'));

        if ($token === '') {
            throw new RuntimeException('No active Upstox token is available. Approve the pending Upstox token request.');
        }

        return $this->rejectExpiredJwt($token, 'UPSTOX_ACCESS_TOKEN/UPSTOX_TOKEN');
    }

    /** @return array{requested: bool, authorization_expires_at: ?Carbon, notifier_url: ?string} */
    public function requestRenewal(bool $force = false): array
    {
        $clientId = trim((string) config('market_data.upstox.client_id'));
        $clientSecret = trim((string) config('market_data.upstox.client_secret'));
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('UPSTOX_CLIENT_ID and UPSTOX_CLIENT_SECRET must be configured to renew tokens.');
        }

        $record = UpstoxAccessToken::query()->where('client_id', $clientId)->first();
        if (! $force) {
            $pending = $record;
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
            ->withOptions(['verify' => TlsCaBundle::resolve(config('market_data.ca_bundle'))])
            ->connectTimeout(10)
            ->timeout(30)
            ->post($url, ['client_secret' => $clientSecret]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            $message = data_get($response->json(), 'errors.0.message') ?: $response->body();
            throw new RuntimeException('Upstox token renewal request failed with HTTP '.$response->status().($message ? ': '.$message : '.'));
        }

        $authorizationExpiry = $this->fromMilliseconds($response->json('data.authorization_expiry'));
        $notifierUrl = $response->json('data.notifier_url');
        UpstoxAccessToken::query()->updateOrCreate(['client_id' => $clientId], [
            'status' => $record?->status === 'active' ? 'active' : 'pending',
            'renewal_requested_at' => now()->utc(),
            'authorization_expires_at' => $authorizationExpiry,
            'metadata' => array_merge($record?->metadata ?? [], ['notifier_url' => $notifierUrl]),
        ]);

        return [
            'requested' => true,
            'authorization_expires_at' => $authorizationExpiry,
            'notifier_url' => $notifierUrl,
        ];
    }

    /** Reject the current database token and request one replacement. */
    public function handleUnauthorized(): array
    {
        if (trim((string) config('market_data.upstox.analytics_token')) !== '') {
            return [
                'requested' => false,
                'authorization_expires_at' => null,
                'notifier_url' => null,
            ];
        }

        $clientId = trim((string) config('market_data.upstox.client_id'));
        UpstoxAccessToken::query()
            ->when($clientId !== '', fn ($query) => $query->where('client_id', $clientId))
            ->where('status', 'active')
            ->update(['status' => 'unauthorized']);

        // A provider 401 is authoritative even when a previous approval
        // request is still pending: request a fresh authorization each time.
        return $this->requestRenewal(force: true);
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

        $issuedAt = $this->fromMilliseconds($payload['issued_at']);
        $providerExpiresAt = $this->fromMilliseconds($payload['expires_at']);
        $expiresAt = $providerExpiresAt->min(
            $issuedAt->copy()->addYears($this->lifetimeYears())
        );

        return DB::transaction(function () use ($payload, $clientId, $issuedAt, $providerExpiresAt, $expiresAt) {
            $record = UpstoxAccessToken::query()
                ->where('client_id', $clientId)
                ->lockForUpdate()
                ->first() ?: new UpstoxAccessToken(['client_id' => $clientId]);
            $record->fill([
                'access_token' => (string) $payload['access_token'],
                'token_type' => (string) ($payload['token_type'] ?? 'Bearer'),
                'upstox_user_id' => isset($payload['user_id']) ? (string) $payload['user_id'] : null,
                'status' => 'active',
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'renewal_requested_at' => null,
                'authorization_expires_at' => null,
                'metadata' => [
                    'message_type' => 'access_token',
                    'provider_expires_at' => $providerExpiresAt->toIso8601String(),
                ],
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

    private function lifetimeYears(): int
    {
        return max(1, (int) config('market_data.upstox.token_lifetime_years', 10));
    }

    private function rejectExpiredJwt(string $token, string $configuration): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $token;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $claims = json_decode((string) base64_decode($payload, true), true);
        if (! is_array($claims) || ! is_numeric($claims['exp'] ?? null)) {
            return $token;
        }

        $expiresAt = Carbon::createFromTimestampUTC((int) $claims['exp']);
        if ($expiresAt->isPast()) {
            throw new RuntimeException(
                $configuration.' expired at '.$expiresAt->setTimezone(config('app.timezone'))->toIso8601String().
                '. Replace it with a current Upstox token.'
            );
        }

        return $token;
    }
}
