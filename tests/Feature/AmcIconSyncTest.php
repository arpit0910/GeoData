<?php

namespace Tests\Feature;

use App\Models\Amc;
use App\Services\AmcSyncService;
use App\Services\MutualFundDetailsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmcIconSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (File::glob(public_path('assets/amc-icons/999999-*.svg')) as $file) {
            File::delete($file);
        }
        parent::tearDown();
    }

    private function directory(): string
    {
        $members = [['mf_id' => '999999', 'mf_name' => 'Example Mutual Fund',
            'amc_name' => 'Example Asset Management Ltd', 'amc_website' => 'https://example.test',
            'icons' => [['url' => '/uploads/example.svg']]]];
        return '<script>self.__next_f.push('.json_encode([1, '5:'.json_encode(['members' => $members])."\n"]).')</script>';
    }

    public function test_icons_are_saved_with_amc_details_and_reused_on_the_next_sync(): void
    {
        Http::fake([
            'https://www.amfiindia.com/member/53' => Http::response($this->directory()),
            'https://www.amfiindia.com/uploads/example.svg' => Http::response('<?xml version="1.0"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h10v10z"/></svg>'),
        ]);
        $this->artisan('amcs:sync-icons')->assertSuccessful();
        $amc = Amc::firstOrFail();
        $this->assertFileExists(public_path($amc->logo_path));
        $this->assertStringNotContainsString('DOCTYPE', File::get(public_path($amc->logo_path)));
        $this->assertSame('Example Asset Management Ltd', $amc->company_name);
        $this->assertSame('Example Mutual Fund', $amc->details['mf_name']);
        $this->artisan('amcs:sync-icons')->expectsOutputToContain('1 already present')->assertSuccessful();
        $this->assertDatabaseCount('amcs', 1);
        Http::assertSentCount(3);

        DB::table('mutual_funds')->insert([
            'isin' => 'INF000000001', 'scheme_code' => '123456', 'scheme_name' => 'Example Growth Fund',
            'amc_name' => 'Example Mutual Fund', 'is_active' => true,
        ]);
        $details = app(MutualFundDetailsService::class)->find('INF000000001');
        $this->assertSame($amc->icon_url, $details['amc_icon_url']);
        $this->assertSame('Example Asset Management Ltd', $details['amc']['company_name']);
        $this->getJson('/api/v1/market/amcs')->assertOk()->assertJsonPath('data.0.icon_url', $amc->icon_url);
        $this->getJson('/api/v1/market/mutual-funds')->assertOk()->assertJsonPath('data.0.amc_icon_url', $amc->icon_url);
        $this->get(route('market.mutual-funds'))->assertOk()->assertSee($amc->logo_path);
    }

    public function test_failed_icon_refresh_preserves_the_last_download(): void
    {
        $amc = Amc::create(['amfi_id' => 999999, 'name' => 'Example Mutual Fund', 'logo_path' => 'assets/amc-icons/previous.svg']);
        Http::fake([
            'https://www.amfiindia.com/member/53' => Http::response($this->directory()),
            'https://www.amfiindia.com/uploads/example.svg' => Http::response('<svg><script>alert(1)</script></svg>'),
        ]);
        $this->artisan('amcs:sync-icons', ['--force' => true])->assertFailed();
        $this->assertSame('assets/amc-icons/previous.svg', $amc->fresh()->logo_path);
    }

    public function test_unknown_directory_format_does_not_erase_existing_amcs(): void
    {
        Amc::create(['amfi_id' => 999999, 'name' => 'Example Mutual Fund']);
        Http::fake(['*' => Http::response('<html>No directory</html>')]);
        $this->artisan('amcs:sync-icons')->assertFailed();
        $this->assertDatabaseCount('amcs', 1);
    }
}
