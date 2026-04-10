<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    /** @test */
    public function it_shows_login_page()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_shows_register_page()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect(route('profile.complete'));
        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
        $this->assertAuthenticated();
    }

    /** @test */
    public function registration_requires_strong_password()
    {
        $response = $this->post('/register', [
            'name' => 'Weak Password User',
            'email' => 'weak@test.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@test.com']);
    }

    /** @test */
    public function registration_requires_unique_email()
    {
        $existing = $this->createUser(['email' => 'duplicate@test.com']);

        $response = $this->post('/register', [
            'name' => 'Duplicate User',
            'email' => 'duplicate@test.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = $this->createUser(['email' => 'login@test.com']);

        $response = $this->post('/login', [
            'email' => 'login@test.com',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_fails_with_wrong_password()
    {
        $user = $this->createUser(['email' => 'wrong@test.com']);

        $response = $this->post('/login', [
            'email' => 'wrong@test.com',
            'password' => 'WrongPassword@123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_logout()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_cannot_visit_login_page()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect();
    }

    /** @test */
    public function authenticated_user_cannot_visit_register_page()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/register');
        $response->assertRedirect();
    }

    /** @test */
    public function it_generates_client_keys_on_registration()
    {
        $this->post('/register', [
            'name' => 'API User',
            'email' => 'apiuser@test.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $user = \App\Models\User::where('email', 'apiuser@test.com')->first();
        $this->assertNotNull($user->client_key);
        $this->assertNotNull($user->client_secret);
        $this->assertStringStartsWith('ck_', $user->client_key);
        $this->assertStringStartsWith('secret_', $user->client_secret);
    }

    /** @test */
    public function it_shows_forgot_password_page()
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }
}
