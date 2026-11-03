<?php

namespace Tests\Feature\Api;

use App\Models\Amc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmcListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_returns_all_amcs_alphabetically_with_their_favicons(): void
    {
        Amc::create(['amfi_id' => 2, 'name' => 'Zeta Mutual Fund']);
        Amc::create([
            'amfi_id' => 1,
            'name' => 'Alpha Mutual Fund',
            'company_name' => 'Alpha Asset Management Ltd',
            'website' => 'https://alpha.example',
            'logo_path' => 'assets/amc-favicons/alpha.png',
            'details' => ['mf_name' => 'Alpha Mutual Fund'],
        ]);

        $response = $this->getJson('/api/v1/market/amcs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Mutual Fund')
            ->assertJsonPath('data.0.company_name', 'Alpha Asset Management Ltd')
            ->assertJsonPath('data.0.website', 'https://alpha.example')
            ->assertJsonPath('data.0.favicon_url', asset('assets/amc-favicons/alpha.png'))
            ->assertJsonPath('data.0.details.mf_name', 'Alpha Mutual Fund')
            ->assertJsonPath('data.1.name', 'Zeta Mutual Fund')
            ->assertJsonPath('data.1.favicon_url', null);

        $this->assertArrayNotHasKey('logo_path', $response->json('data.0'));
    }

    public function test_an_empty_amc_directory_returns_an_empty_list(): void
    {
        $this->getJson('/api/v1/market/amcs')->assertOk()->assertExactJson(['success' => true, 'data' => []]);
    }
}
