<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BankApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $bankData;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->authData = $this->createAuthenticatedApiUser();
        $this->bankData = $this->createBankData();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    /** @test */
    public function it_fetches_all_banks()
    {
        $response = $this->getJson('/api/v1/banks', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function it_filters_banks_by_name()
    {
        $response = $this->getJson('/api/v1/banks?name=State+Bank', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('State Bank', $data[0]['name']);
    }

    /** @test */
    public function it_fetches_bank_branches()
    {
        $response = $this->getJson('/api/v1/banks/' . $this->bankData['bank']->id . '/branches', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_fetches_bank_coverage()
    {
        $response = $this->getJson('/api/v1/banks/' . $this->bankData['bank']->id . '/coverage', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['bank', 'total_branches', 'total_states', 'total_cities', 'states'],
            ]);
    }

    /** @test */
    public function it_searches_branches()
    {
        $response = $this->getJson('/api/v1/branch/search?search_query=Fort', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function it_returns_error_for_short_search_query()
    {
        $response = $this->getJson('/api/v1/branch/search?search_query=F', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_gets_branch_by_ifsc()
    {
        $response = $this->getJson('/api/v1/branch/SBIN0000001', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_returns_404_for_invalid_ifsc()
    {
        $response = $this->getJson('/api/v1/branch/INVALID0001', $this->headers);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_fetches_banks_in_city()
    {
        $response = $this->getJson('/api/v1/cities/' . $this->bankData['city']->id . '/banks', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_fetches_banks_in_state()
    {
        $response = $this->getJson('/api/v1/states/' . $this->bankData['state']->id . '/banks', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_fetches_banks_in_country()
    {
        $response = $this->getJson('/api/v1/countries/' . $this->bankData['country']->id . '/banks', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_fetches_banks_by_pincode()
    {
        $response = $this->getJson('/api/v1/pincodes/400001/banks', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
