<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\CheckApiCredits;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureSubscribed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    /** @test */
    public function check_api_credits_rejects_unauthenticated()
    {
        $middleware = new CheckApiCredits();
        $request = Request::create('/api/v1/regions', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->status());
    }

    /** @test */
    public function is_admin_redirects_non_admin()
    {
        $user = $this->createUser();

        $middleware = new IsAdmin();
        $request = Request::create('/user/list', 'GET');
        $request->setUserResolver(fn() => $user);

        // Simulate auth
        $this->actingAs($user);

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(302, $response->status());
    }

    /** @test */
    public function is_admin_allows_admin()
    {
        $admin = $this->createAdminUser();

        $middleware = new IsAdmin();
        $request = Request::create('/user/list', 'GET');
        $request->setUserResolver(fn() => $admin);

        $this->actingAs($admin);

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function send_response_helper_formats_correctly()
    {
        $response = sendResponse(['key' => 'value'], 'Test message', 200);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Test message', $data['message']);
        $this->assertArrayHasKey('key', $data['data']);
    }

    /** @test */
    public function send_response_helper_marks_error_correctly()
    {
        $response = sendResponse(null, 'Error occurred', 500);
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals(500, $response->status());
    }
}
