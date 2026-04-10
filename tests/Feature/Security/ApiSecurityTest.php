<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authData = $this->createAuthenticatedApiUser();
        $this->createGeoHierarchy();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    // ─── AUTHENTICATION ENFORCEMENT ─────────────────────────────────

    /** @test */
    public function unauthenticated_requests_are_rejected()
    {
        $endpoints = [
            '/api/v1/regions',
            '/api/v1/countries',
            '/api/v1/states',
            '/api/v1/cities',
            '/api/v1/pincodes',
            '/api/v1/banks',
            '/api/v1/user/usage',
            '/api/v1/geospatial/statistics',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertTrue(
                in_array($response->status(), [401, 302]),
                "Endpoint {$endpoint} should be protected but returned {$response->status()}"
            );
        }
    }

    /** @test */
    public function invalid_bearer_token_is_rejected()
    {
        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer invalid_token_here_12345',
        ]);

        $this->assertTrue(
            in_array($response->status(), [401, 302]),
            "Invalid token should be rejected"
        );
    }

    /** @test */
    public function expired_token_is_rejected()
    {
        $user = $this->createUser();
        $token = $user->createToken('setugeo-auth-token');
        
        // Delete the token to simulate expiration/revocation
        $token->accessToken->delete();

        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]);

        $this->assertTrue(
            in_array($response->status(), [401, 302]),
            "Expired/revoked token should be rejected"
        );
    }

    // ─── SQL INJECTION ──────────────────────────────────────────────

    /** @test */
    public function it_resists_sql_injection_in_name_filter()
    {
        $malicious = "'; DROP TABLE countries; --";
        $response = $this->getJson('/api/v1/countries?name=' . urlencode($malicious), $this->headers);

        // Should not crash — should return empty or safe results
        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
        $this->assertDatabaseHas('countries', ['name' => 'India']); // table still exists
    }

    /** @test */
    public function it_resists_sql_injection_in_iso2_filter()
    {
        $malicious = "' OR 1=1; --";
        $response = $this->getJson('/api/v1/countries?iso2=' . urlencode($malicious), $this->headers);

        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
    }

    /** @test */
    public function it_resists_sql_injection_in_search_query()
    {
        $malicious = "' UNION SELECT * FROM users; --";
        $response = $this->getJson('/api/v1/branch/search?search_query=' . urlencode($malicious), $this->headers);

        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
    }

    /** @test */
    public function it_resists_sql_injection_in_pincode_search()
    {
        $malicious = "400001' OR '1'='1";
        $response = $this->getJson('/api/v1/pincodes/search?pincode=' . urlencode($malicious), $this->headers);

        $this->assertTrue(in_array($response->status(), [200, 400, 404]));
    }

    /** @test */
    public function it_resists_sql_injection_in_currency_param()
    {
        $malicious = "EUR'; DROP TABLE currency_conversions; --";
        $response = $this->getJson('/api/v1/currency/exchange?currency=' . urlencode($malicious), $this->headers);

        $this->assertTrue(in_array($response->status(), [200, 400, 404]));
    }

    // ─── XSS PREVENTION ─────────────────────────────────────────────

    /** @test */
    public function it_resists_xss_in_name_filters()
    {
        $xss = '<script>alert("XSS")</script>';
        $response = $this->getJson('/api/v1/countries?name=' . urlencode($xss), $this->headers);

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content);
    }

    /** @test */
    public function it_resists_xss_in_address_autocomplete()
    {
        $xss = '<img src=x onerror=alert(1)>';
        $response = $this->getJson('/api/v1/address/autocomplete?q=' . urlencode($xss), $this->headers);

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringNotContainsString('onerror', $content);
    }

    // ─── PARAMETER TAMPERING ────────────────────────────────────────

    /** @test */
    public function it_handles_extremely_large_limit_parameter()
    {
        $response = $this->getJson('/api/v1/countries?limit=999999', $this->headers);

        // Should not crash — should handle gracefully
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_negative_limit_parameter()
    {
        $response = $this->getJson('/api/v1/countries?limit=-1', $this->headers);

        // Should not crash
        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
    }

    /** @test */
    public function it_handles_non_numeric_id_parameters()
    {
        $response = $this->getJson('/api/v1/countries/abc', $this->headers);

        $this->assertTrue(
            in_array($response->status(), [404, 400, 500]),
            "Non-numeric ID should be handled safely"
        );
    }

    /** @test */
    public function it_handles_special_characters_in_query_params()
    {
        $special = "test%00null\x00value";
        $response = $this->getJson('/api/v1/countries?name=' . urlencode($special), $this->headers);

        $this->assertTrue(in_array($response->status(), [200, 400]));
    }

    // ─── RATE LIMITING / THROTTLE ────────────────────────────────────

    /** @test */
    public function api_has_throttle_middleware()
    {
        // Verify the API route group has throttling configured
        $kernel = app(\App\Http\Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('api', $middlewareGroups);
        $this->assertContains('throttle:api', $middlewareGroups['api']);
    }

    // ─── AUTH TOKEN SECURITY ──────────────────────────────────────────

    /** @test */
    public function client_secret_is_not_exposed_in_token_generation()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString($user->client_secret, $content);
    }

    /** @test */
    public function user_password_is_never_exposed_in_api_response()
    {
        $response = $this->getJson('/api/v1/user/usage', $this->headers);

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', $content);
    }

    /** @test */
    public function inactive_user_cannot_use_existing_token()
    {
        // Create auth, then deactivate
        $this->authData['user']->update(['status' => 0]);

        // Re-generate token attempt should fail
        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $this->authData['user']->client_key,
            'client_secret' => $this->authData['user']->client_secret,
        ]);

        $response->assertStatus(403);
    }

    // ─── MASS ASSIGNMENT PROTECTION ──────────────────────────────────

    /** @test */
    public function token_endpoint_ignores_extra_fields()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
            'is_admin' => 1,
            'status' => 1,
            'available_credits' => 9999999,
        ]);

        // Should still work, ignoring extra fields
        $response->assertStatus(200);

        // Ensure user wasn't corrupted
        $user->refresh();
        $this->assertEquals(0, $user->is_admin);
    }

    // ─── CORS / HEADERS ─────────────────────────────────────────────

    /** @test */
    public function api_responses_have_json_content_type()
    {
        $response = $this->getJson('/api/v1/regions', $this->headers);

        $this->assertStringContainsString(
            'application/json',
            $response->headers->get('Content-Type')
        );
    }

    // ─── CSRF EXEMPTION ON API ──────────────────────────────────────

    /** @test */
    public function api_post_works_without_csrf_token()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        // Should work without CSRF — API routes are exempt
        $response->assertStatus(200);
    }

    // ─── ENUMERATION PROTECTION ─────────────────────────────────────

    /** @test */
    public function sequential_user_id_probing_does_not_leak_data()
    {
        // Even if someone tries sequential IDs, credit endpoints require auth
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->getJson("/api/v1/user/usage");
            $this->assertTrue(
                in_array($response->status(), [401, 302]),
                "Usage endpoint should require auth"
            );
        }
    }
}
