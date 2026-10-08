<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EquityApiController;
use App\Models\Equity;
use App\Services\UpstoxInstrumentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
                'short_name' => 'Reliance',
                'security_type' => 'NORMAL',
                'exchange_token' => '2885',
                'tick_size' => 5,
                'freeze_quantity' => 100000,
                'qty_multiplier' => 1,
                'mtf_enabled' => true,
                'mtf_bracket' => 26.5,
                'cas_eligible' => true,
                'intraday_margin' => 20,
                'intraday_leverage' => 5,
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
            'short_name' => 'Reliance',
            'security_type' => 'NORMAL',
            'nse_exchange_token' => '2885',
            'nse_tick_size' => 5,
            'nse_freeze_quantity' => 100000,
            'qty_multiplier' => 1,
            'mtf_enabled' => 1,
            'mtf_bracket' => 26.5,
            'cas_eligible' => 1,
            'intraday_margin' => 20,
            'intraday_leverage' => 5,
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

    public function test_command_downloads_the_current_instrument_master_when_no_file_is_given(): void
    {
        config(['market_data.upstox.instruments_url' => 'https://provider.test/complete.json.gz']);
        Http::fake([
            'https://provider.test/complete.json.gz' => Http::response(gzencode(json_encode([[
                'segment' => 'NSE_EQ',
                'name' => 'AUTOMATED COMPANY LIMITED',
                'short_name' => 'Automated Company',
                'isin' => 'INE123A01010',
                'instrument_type' => 'EQ',
                'instrument_key' => 'NSE_EQ|INE123A01010',
                'exchange_token' => '12345',
                'lot_size' => 1,
                'tick_size' => 5,
                'trading_symbol' => 'AUTOCO',
            ]], JSON_THROW_ON_ERROR))),
        ]);

        $this->artisan('equities:sync-upstox-instruments')
            ->expectsOutputToContain('Downloading the current Upstox instrument master')
            ->assertExitCode(0);

        $this->assertDatabaseHas('equities', [
            'isin' => 'INE123A01010',
            'company_name' => 'AUTOMATED COMPANY LIMITED',
            'short_name' => 'Automated Company',
            'nse_symbol' => 'AUTOCO',
            'nse_exchange_token' => '12345',
        ]);
    }

    public function test_admin_can_upload_and_sync_the_master_in_small_chunks(): void
    {
        $admin = $this->createAdminUser();
        $json = json_encode([
            [
                'segment' => 'BSE_EQ',
                'name' => 'CHUNKED COMPANY LTD',
                'isin' => 'INE123A01010',
                'instrument_type' => 'A',
                'instrument_key' => 'BSE_EQ|INE123A01010',
                'lot_size' => 1,
                'trading_symbol' => 'CHUNKED',
            ],
        ], JSON_THROW_ON_ERROR);
        $splitAt = (int) floor(strlen($json) / 2);
        $uploadId = '12345678-1234-1234-1234-123456789012';

        $first = $this->actingAs($admin)->postJson(route('equities.upstox.import.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'original_name' => 'complete.json',
            'chunk' => UploadedFile::fake()->createWithContent('0.part', substr($json, 0, $splitAt)),
        ]);
        $first->assertOk()->assertJsonPath('complete', false);

        $second = $this->actingAs($admin)->postJson(route('equities.upstox.import.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 1,
            'total_chunks' => 2,
            'original_name' => 'complete.json',
            'chunk' => UploadedFile::fake()->createWithContent('1.part', substr($json, $splitAt)),
        ]);

        $second->assertOk()
            ->assertJsonPath('complete', true)
            ->assertJsonPath('data.unique_isins', 1);
        $this->assertStringNotContainsString('BSE_EQ|INE123A01010', $second->getContent());
        $this->assertDatabaseHas('equities', [
            'isin' => 'INE123A01010',
            'bse_symbol' => 'CHUNKED',
            'upstox_bse_instrument_key' => 'BSE_EQ|INE123A01010',
        ]);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function instrumentFile(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upstox-instruments-');
        file_put_contents($path, json_encode($rows, JSON_THROW_ON_ERROR));

        return $path;
    }
}
