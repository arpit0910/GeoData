<?php

namespace Tests\Feature;

use App\Models\UpstoxAccessToken;
use App\Services\UpstoxTokenManager;
use App\Services\UpstoxMarketDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'market_data.upstox.client_id' => 'client-123',
            'market_data.upstox.client_secret' => 'secret-456',
            'market_data.upstox.notifier_secret' => 'webhook-secret',
            'market_data.upstox.token_request_url' => 'https://provider.test/v3/login/auth/token/request',
            'market_data.upstox.access_token' => null,
            'market_data.upstox.token_lifetime_years' => 10,
        ]);
    }

    public function test_it_requests_a_replacement_only_once_while_approval_is_pending(): void
    {
        $expires = now()->addHours(3)->getTimestampMs();
        Http::fake([
            'https://provider.test/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'authorization_expiry' => (string) $expires,
                    'notifier_url' => 'https://example.test/api/v1/integrations/market-data/upstox-token/webhook-secret',
                ],
            ]),
        ]);

        $manager = app(UpstoxTokenManager::class);
        $this->assertTrue($manager->requestRenewal()['requested']);
        $this->assertFalse($manager->requestRenewal()['requested']);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['client_secret'] === 'secret-456');
        $this->assertDatabaseHas('upstox_access_tokens', ['client_id' => 'client-123', 'status' => 'pending']);
    }

    public function test_notifier_stores_the_token_encrypted_and_it_becomes_the_live_token(): void
    {
        $issuedAt = Carbon::now()->subMinute();
        $expiresAt = Carbon::now()->addHours(12);
        UpstoxAccessToken::create([
            'client_id' => 'client-123',
            'status' => 'pending',
            'renewal_requested_at' => now(),
            'authorization_expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/integrations/market-data/upstox-token/webhook-secret', [
            'client_id' => 'client-123',
            'user_id' => 'USER1',
            'access_token' => 'live-secret-token',
            'token_type' => 'Bearer',
            'issued_at' => (string) $issuedAt->getTimestampMs(),
            'expires_at' => (string) $expiresAt->getTimestampMs(),
            'message_type' => 'access_token',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $rawToken = DB::table('upstox_access_tokens')->value('access_token');
        $this->assertNotSame('live-secret-token', $rawToken);
        $this->assertSame('live-secret-token', app(UpstoxTokenManager::class)->accessToken());
    }

    public function test_notifier_limits_a_long_provider_expiry_to_ten_years(): void
    {
        $issuedAt = Carbon::now()->startOfSecond();
        $providerExpiresAt = $issuedAt->copy()->addYears(20);

        $response = $this->postJson('/api/v1/integrations/market-data/upstox-token/webhook-secret', [
            'client_id' => 'client-123',
            'user_id' => 'USER1',
            'access_token' => 'long-lived-provider-token',
            'token_type' => 'Bearer',
            'issued_at' => (string) $issuedAt->getTimestampMs(),
            'expires_at' => (string) $providerExpiresAt->getTimestampMs(),
            'message_type' => 'access_token',
        ]);

        $response->assertOk();
        $token = UpstoxAccessToken::firstOrFail();

        $this->assertTrue($token->expires_at->equalTo($issuedAt->copy()->addYears(10)));
        $this->assertSame(
            $providerExpiresAt->toIso8601String(),
            data_get($token->metadata, 'provider_expires_at')
        );
    }

    public function test_notifier_rejects_an_invalid_secret(): void
    {
        $this->postJson('/api/v1/integrations/market-data/upstox-token/wrong-secret', [])
            ->assertNotFound();
        $this->assertDatabaseCount('upstox_access_tokens', 0);
    }

    public function test_a_token_older_than_one_day_is_reused_when_it_has_a_future_expiry(): void
    {
        UpstoxAccessToken::create([
            'client_id' => 'client-123',
            'access_token' => 'old-long-lived-token',
            'status' => 'active',
            'issued_at' => now()->subDays(2),
            'expires_at' => now()->addYears(10),
        ]);

        $this->assertSame('old-long-lived-token', app(UpstoxTokenManager::class)->accessToken());
    }

    public function test_http_401_invalidates_the_token_and_requests_one_replacement(): void
    {
        UpstoxAccessToken::create([
            'client_id' => 'client-123',
            'access_token' => 'rejected-token',
            'status' => 'active',
            'issued_at' => now()->subHour(),
            'expires_at' => now()->addHours(12),
        ]);

        config(['market_data.upstox.ltp_url' => 'https://provider.test/v3/market-quote/ltp']);
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/market-quote/ltp')) {
                return Http::response([
                    'status' => 'error',
                    'errors' => [['message' => 'Invalid token used to access API']],
                ], 401);
            }

            return Http::response([
                'status' => 'success',
                'data' => [
                    'authorization_expiry' => (string) now()->addHour()->getTimestampMs(),
                    'notifier_url' => 'https://example.test/upstox/notifier',
                ],
            ]);
        });

        try {
            app(UpstoxMarketDataService::class)->ltp(['NSE_EQ|INE002A01018']);
            $this->fail('The rejected Upstox request should throw an exception.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
        }

        $this->assertDatabaseHas('upstox_access_tokens', ['status' => 'unauthorized']);
        $this->assertDatabaseHas('upstox_access_tokens', ['status' => 'pending']);
        Http::assertSentCount(2);
    }
}
