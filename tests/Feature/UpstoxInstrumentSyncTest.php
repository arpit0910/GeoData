<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\EquityApiController;
use App\Models\Equity;
use App\Services\UpstoxInstrumentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class UpstoxInstrumentSyncTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_it_syncs_both_exchange_keys_and_adds_missing_instruments(): void
    {
        Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Old Reliance Name',
            'nse_symbol' => 'OLD',
            'is_active' => false,
        ]);

        $path = $this->instrumentFile([
            [
                'segment' => 'NSE_EQ',
                'name' => 'RELIANCE INDUSTRIES LTD',
                'isin' => 'INE002A01018',
                'instrument_type' => 'EQ',
                'instrument_key' => 'NSE_EQ|INE002A01018',
                'lot_size' => 1,
                'trading_symbol' => 'RELIANCE',
            ],
            [
                'segment' => 'BSE_EQ',
                'name' => 'RELIANCE INDUSTRIES LTD',
                'isin' => 'INE002A01018',
                'instrument_type' => 'A',
                'instrument_key' => 'BSE_EQ|INE002A01018',
                'lot_size' => 1,
                'trading_symbol' => 'RELIANCE',
            ],
            [
                'segment' => 'BSE_EQ',
                'name' => 'NEW LISTED COMPANY',
                'isin' => 'INE123A01010',
                'instrument_type' => 'B',
                'instrument_key' => 'BSE_EQ|INE123A01010',
                'lot_size' => 10,
                'trading_symbol' => 'NEWCO',
            ],
            [
                'segment' => 'NSE_FO',
                'name' => 'IGNORED FUTURE',
                'instrument_key' => 'NSE_FO|12345',
            ],
        ]);

        try {
            $stats = app(UpstoxInstrumentSyncService::class)->sync($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame(4, $stats['scanned']);
        $this->assertSame(2, $stats['unique_isins']);
        $this->assertSame(1, $stats['added']);
        $this->assertSame(1, $stats['updated']);
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE002A01018',
            'company_name' => 'RELIANCE INDUSTRIES LTD',
            'nse_symbol' => 'RELIANCE',
            'bse_symbol' => 'RELIANCE',
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
            'upstox_bse_instrument_key' => 'BSE_EQ|INE002A01018',
            'series' => 'EQ',
            'market_lot' => 1,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE123A01010',
            'bse_symbol' => 'NEWCO',
            'upstox_bse_instrument_key' => 'BSE_EQ|INE123A01010',
        ]);
    }

    public function test_upstox_keys_are_hidden_from_model_and_public_api_serialisation(): void
    {
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'is_active' => true,
        ]);
        DB::table('equities')->where('id', $equity->id)->update([
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
        ]);

        $equity->refresh();
        $this->assertArrayNotHasKey('upstox_nse_instrument_key', $equity->toArray());

        $response = app(EquityApiController::class)->index(Request::create('/api/v1/equities'));
        $this->assertStringNotContainsString('upstox_', $response->getContent());
        $this->assertStringNotContainsString('NSE_EQ|INE002A01018', $response->getContent());
    }

    public function test_only_admin_equity_listing_explicitly_reveals_upstox_keys(): void
    {
        $admin = $this->createAdminUser();
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'is_active' => true,
        ]);
        DB::table('equities')->where('id', $equity->id)->update([
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
        ]);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('equities.index'))
            ->assertOk()
            ->assertJsonPath('data.0.upstox_nse_instrument_key', 'NSE_EQ|INE002A01018');
    }

    public function test_admin_can_upload_an_upstox_json_master_without_keys_in_the_response(): void
    {
        $admin = $this->createAdminUser();
        $file = UploadedFile::fake()->createWithContent('complete.json', json_encode([
            [
                'segment' => 'NSE_EQ',
                'name' => 'RELIANCE INDUSTRIES LTD',
                'isin' => 'INE002A01018',
                'instrument_type' => 'EQ',
                'instrument_key' => 'NSE_EQ|INE002A01018',
                'lot_size' => 1,
                'trading_symbol' => 'RELIANCE',
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $this->actingAs($admin)->postJson(route('equities.upstox.import'), [
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unique_isins', 1)
            ->assertJsonMissingPath('data.instruments');
        $this->assertStringNotContainsString('NSE_EQ|INE002A01018', $response->getContent());
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE002A01018',
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
        ]);
    }

    public function test_non_admin_cannot_upload_an_upstox_json_master(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->createWithContent('complete.json', '[]');

        $response = $this->actingAs($user)->postJson(route('equities.upstox.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('equities', ['isin' => 'INE002A01018']);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function instrumentFile(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upstox-instruments-');
        file_put_contents($path, json_encode($rows, JSON_THROW_ON_ERROR));

        return $path;
    }
}
