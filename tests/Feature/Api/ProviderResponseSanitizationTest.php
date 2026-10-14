<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProviderResponseSanitizationTest extends TestCase
{
    public function test_api_responses_remove_provider_named_keys_and_branding(): void
    {
        Route::middleware('api')->get('/api/test-provider-sanitization', fn () => response()->json([
            'upstox_key' => 'secret',
            'nested' => [
                'UPSTOX_metadata' => ['secret' => true],
                'description' => 'Delivered by Upstox infrastructure',
            ],
        ]));

        $response = $this->getJson('/api/test-provider-sanitization');

        $response->assertOk()
            ->assertJsonMissingPath('upstox_key')
            ->assertJsonMissingPath('nested.UPSTOX_metadata')
            ->assertJsonPath('nested.description', 'Delivered by market data provider infrastructure');
        $this->assertStringNotContainsStringIgnoringCase('upstox', $response->getContent());
    }
}
