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
            ->getJson('/api/v1/upstox/callback?code=test-authorization-code&state=test-state');

        $response->assertOk()->assertExactJson([
            'success' => true,
            'message' => 'Upstox callback received.',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Upstox OAuth callback received.', Mockery::on(function (array $context): bool {
                return $context['query'] === [
                    'code' => 'test-authorization-code',
                    'state' => 'test-state',
                ] && $context['user_agent'] === 'Upstox-Test';
            }));
    }
}
