<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    /** @test */
    public function it_generates_token_with_valid_credentials()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['token_type' => 'Bearer'],
            ]);
    }

    /** @test */
    public function it_rejects_invalid_client_key()
    {
        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => 'ck_invalid_key_12345',
            'client_secret' => 'secret_invalid',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_invalid_client_secret()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => 'secret_wrong_12345',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_inactive_user()
    {
        $user = $this->createInactiveUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_key', 'client_secret']);
    }

    /** @test */
    public function it_revokes_old_tokens_on_new_generation()
    {
        $user = $this->createUser();

        // Generate first token
        $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $this->assertEquals(1, $user->tokens()->count());

        // Generate second token — should revoke the first
        $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $this->assertEquals(1, $user->tokens()->count());
    }

    /** @test */
    public function token_response_time_is_acceptable()
    {
        $user = $this->createUser();

        $start = microtime(true);

        $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $elapsed = (microtime(true) - $start) * 1000; // ms

        $this->assertLessThan(500, $elapsed, "Token generation took {$elapsed}ms — should be under 500ms");
    }
}
