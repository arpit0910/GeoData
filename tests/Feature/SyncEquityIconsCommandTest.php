<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EquityApiController;
use App\Http\Controllers\Api\MarketDataController;
use App\Models\Equity;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->iconPath = public_path('assets/equity-icons/INE002A01018.svg');
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
            'isin' => 'INE002A01018',
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
            'isin' => 'INE002A01018',
            'logo_url' => asset('assets/equity-icons/INE002A01018.svg'),
        ]);
        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('equities.index'))
            ->assertOk()
            ->assertJsonPath('data.0.logo_url', asset('assets/equity-icons/INE002A01018.svg'));

        $api = app(EquityApiController::class)
            ->index(Request::create('/api/v1/equities'));
        $this->assertSame(
            asset('assets/equity-icons/INE002A01018.svg'),
            $api->getData(true)['data']['data'][0]['logo_url']
        );

        $marketList = app(MarketDataController::class)
            ->stocks(Request::create('/api/v1/market/stocks'));
        $this->assertSame(
            asset('assets/equity-icons/INE002A01018.svg'),
            $marketList->getData(true)['data'][0]['logo_url']
        );
    }

    public function test_it_rejects_unsafe_svg(): void
    {
        Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'is_active' => true,
        ]);
        Http::fake([
            'https://logos.test/*' => Http::response('<svg><script>alert(1)</script></svg>'),
        ]);

        $this->artisan('equities:sync-icons')->assertFailed();
        $this->assertFileDoesNotExist($this->iconPath);
    }
}
