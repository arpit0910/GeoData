<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EquityApiController;
use App\Http\Controllers\Api\MarketDataController;
use App\Models\Equity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class SyncEquityIconsCommandTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private string $iconPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->iconPath = public_path('assets/equity-icons/INE000TEST01.svg');
        File::delete($this->iconPath);
        config(['market_data.equity_icons.url' => 'https://logos.test/logo/{isin}/icon.svg']);
    }

    protected function tearDown(): void
    {
        File::delete($this->iconPath);
        parent::tearDown();
    }

    public function test_it_downloads_a_safe_icon_and_exposes_it_in_the_equities_list(): void
    {
        $admin = $this->createAdminUser();
        Equity::create([
            'isin' => 'INE000TEST01',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        Http::fake([
            'https://logos.test/*' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h10v10z"/></svg>', 200, [
                'Content-Type' => 'image/svg+xml',
            ]),
        ]);

        $this->artisan('equities:sync-icons')->assertSuccessful();

        $this->assertFileExists($this->iconPath);
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE000TEST01',
            'logo_url' => asset('assets/equity-icons/INE000TEST01.svg'),
        ]);
        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('equities.index'))
            ->assertOk()
            ->assertJsonPath('data.0.logo_url', asset('assets/equity-icons/INE000TEST01.svg'));

        $api = app(EquityApiController::class)
            ->index(Request::create('/api/v1/equities'));
        $this->assertSame(
            asset('assets/equity-icons/INE000TEST01.svg'),
            $api->getData(true)['data']['data'][0]['logo_url']
        );

        $marketList = app(MarketDataController::class)
            ->stocks(Request::create('/api/v1/market/stocks'));
        $this->assertSame(
            asset('assets/equity-icons/INE000TEST01.svg'),
            $marketList->getData(true)['data'][0]['logo_url']
        );
    }

    public function test_it_rejects_unsafe_svg(): void
    {
        Equity::create([
            'isin' => 'INE000TEST01',
            'company_name' => 'Reliance Industries',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        Http::fake([
            'https://logos.test/*' => Http::response('<svg><script>alert(1)</script></svg>'),
        ]);

        $this->artisan('equities:sync-icons')->assertFailed();
        $this->assertFileDoesNotExist($this->iconPath);
    }

    public function test_it_skips_connection_failures_and_continues_with_the_next_equity(): void
    {
        Equity::create([
            'isin' => 'INE052T01013',
            'company_name' => 'Best Agrolife Limited',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        Equity::create([
            'isin' => 'INE000TEST01',
            'company_name' => 'Reliance Industries',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'INE052T01013')) {
                throw new ConnectionException('Could not connect to server');
            }

            return Http::response('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        });

        $this->artisan('equities:sync-icons')->assertSuccessful();

        $this->assertDatabaseHas('equities', [
            'isin' => 'INE052T01013',
            'logo_url' => null,
        ]);
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE000TEST01',
            'logo_url' => asset('assets/equity-icons/INE000TEST01.svg'),
        ]);
    }

    public function test_it_ignores_non_stock_instruments(): void
    {
        Equity::create([
            'isin' => 'IN002022Z507',
            'company_name' => 'GOI TBILL 364D-14/03/24',
            'series' => 'GS',
            'is_active' => true,
        ]);
        Http::fake();

        $this->artisan('equities:sync-icons')->assertSuccessful();

        Http::assertNothingSent();
    }
}
