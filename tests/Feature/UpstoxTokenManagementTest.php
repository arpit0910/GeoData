<?php

namespace Tests\Feature;

use App\Models\UpstoxAccessToken;
use App\Services\UpstoxTokenManager;
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

    public function test_notifier_rejects_an_invalid_secret(): void
    {
        $this->postJson('/api/v1/integrations/market-data/upstox-token/wrong-secret', [])
            ->assertNotFound();
        $this->assertDatabaseCount('upstox_access_tokens', 0);
    }
}
