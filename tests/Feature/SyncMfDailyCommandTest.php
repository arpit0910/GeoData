<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncMfDailyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_long_amfi_plan_type_does_not_abort_the_daily_sync(): void
    {
        $plan = 'Direct Plan - Monthly Payout of Income Distribution cum capital withdrawal option';
        $option = 'Reinvestment option with an extended AMFI description';
        $type = "{$plan} - {$option}";
        $feed = implode("\n", [
            'Open Ended Schemes(Debt Scheme - Banking and PSU Fund)',
            'Example Mutual Fund',
            'Scheme Code;ISIN Div Payout/ISIN Growth;ISIN Div Reinvestment;Scheme Name;Plan;Option;Net Asset Value;Date',
            "123456;INF000000001;INF000000002;Example Banking and PSU Fund;{$plan};{$option};10.2500;08-Oct-2026",
        ]);

        Http::fake([
            'https://www.amfiindia.com/spages/NAVAll.txt' => Http::response($feed),
        ]);

        $this->artisan('sync:mf-daily', ['--force' => true, '--skip-returns' => true])
            ->assertExitCode(0);

        $this->assertGreaterThan(50, mb_strlen($type));
        $this->assertDatabaseHas('mutual_funds', [
            'isin' => 'INF000000001',
            'type' => $type,
        ]);
        $this->assertDatabaseHas('mutual_fund_prices', [
            'isin' => 'INF000000001',
            'nav_date' => '2026-10-08',
        ]);
    }

    public function test_upstox_daily_rates_recover_an_amfi_failure(): void
    {
        $today = now('Asia/Kolkata')->toDateString();
        DB::table('mutual_funds')->insert([
            'isin' => 'INF000000001',
            'scheme_code' => '123456',
            'scheme_name' => 'Fallback Mutual Fund',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config(['market_data.upstox.mf_instruments_url' => 'https://provider.test/mf.json.gz']);

        Http::fake([
            'https://www.amfiindia.com/spages/NAVAll.txt' => Http::response('Unavailable', 503),
            'https://provider.test/mf.json.gz' => Http::response(gzencode(json_encode([[
                'instrument_key' => 'INF000000001',
                'name' => 'Fallback Mutual Fund',
                'last_price' => 15.4321,
                'last_price_date' => $today,
            ]], JSON_THROW_ON_ERROR))),
        ]);

        $this->artisan('sync:mf-daily', ['--force' => true, '--skip-returns' => true])
            ->expectsOutputToContain('Trying the Upstox daily mutual-fund rates fallback')
            ->expectsOutputToContain('Upstox fallback complete')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mutual_fund_prices', [
            'isin' => 'INF000000001',
            'nav_date' => $today,
            'nav' => 15.4321,
        ]);
    }
}
