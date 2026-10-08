<?php

namespace Tests\Feature;

use App\Console\Commands\MfBackfillCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class MfBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_uses_the_official_amfi_bulk_history_feed(): void
    {
        $fundId = DB::table('mutual_funds')->insertGetId([
            'isin' => 'INF000000001',
            'scheme_code' => '123456',
            'isin_reinvest' => 'INF000000002',
            'scheme_name' => 'Example Mutual Fund',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $feed = implode("\n", [
            'Scheme Code;NAV Name;Plan;Option;ISIN Div Payout/ISIN Growth;ISIN Div Reinvestment;Net Asset Value;Date',
            'Open Ended Schemes ( Debt Scheme - Overnight Fund )',
            'Example Mutual Fund',
            '123456;Example Fund - Direct Growth;Direct Plan;Growth;INF000000001;INF000000002;12.3456;08-Oct-2026',
        ]);

        Http::fake([
            'https://portal.amfiindia.com/*' => Http::response($feed),
        ]);

        $command = $this->app->make(MfBackfillCommand::class);
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--from' => '2026-10-08',
            '--to' => '2026-10-08',
            '--delay' => 0,
        ]);

        Http::assertSent(fn (Request $request) =>
            str_starts_with($request->url(), 'https://portal.amfiindia.com/')
            && ! str_contains($request->url(), 'api.mfapi.in')
        );
        $this->assertDatabaseHas('mutual_fund_prices', [
            'isin' => 'INF000000001',
            'mf_id' => $fundId,
            'nav_date' => '2026-10-08',
            'nav' => 12.3456,
        ]);
        $this->assertSame(0, $exitCode, $tester->getDisplay());
    }
}
