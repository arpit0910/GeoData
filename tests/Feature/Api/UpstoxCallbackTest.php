<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class UpstoxCallbackTest extends TestCase
{
    public function test_callback_logs_the_upstox_response(): void
    {
        Log::spy();
        Log::shouldReceive('channel')->once()->with('upstox')->andReturnSelf();

        $response = $this->withHeader('User-Agent', 'Upstox-Test')
            ->getJson('/api/v1/integrations/market-data/callback?code=test-authorization-code&state=test-state');

        $response->assertOk()->assertExactJson([
            'success' => true,
            'message' => 'Market data authorization received.',
        ]);

        $this->assertStringNotContainsStringIgnoringCase('upstox', $response->getContent());

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Upstox OAuth callback received.', Mockery::on(function (array $context): bool {
                return $context['has_code'] === true
                    && $context['has_state'] === true
                    && $context['user_agent'] === 'Upstox-Test';
            }));
    }
}
