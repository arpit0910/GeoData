<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairMarketPerformanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_equity_repairs_are_processed_in_bounded_batches(): void
    {
        foreach ([1, 2] as $number) {
            $equity = Equity::create([
                'isin' => sprintf('INE00000000%d', $number),
                'company_name' => "Company {$number}",
            ]);
            DB::table('equity_prices')->insert([
                'equity_id' => $equity->id,
                'isin' => $equity->isin,
                'traded_date' => '2026-10-09',
                'nse_close' => 100 * $number,
                'bse_close' => 100 * $number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('equity_prices')->insert([
                'equity_id' => $equity->id,
                'isin' => $equity->isin,
                'traded_date' => '2026-10-12',
                'nse_open' => 105 * $number,
                'nse_high' => 112 * $number,
                'nse_low' => 104 * $number,
                'nse_close' => 110 * $number,
                'nse_prev_close' => 100 * $number,
                'bse_close' => 110 * $number,
                'bse_prev_close' => 100 * $number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $before = EquityPrice::whereDate('traded_date', '2026-10-12')->orderBy('id')->firstOrFail();
        $this->assertSame(110.0, (float) $before->nse_close);
        $this->assertSame(100.0, (float) $before->nse_prev_close);

        $this->artisan('market:repair-performance-values', [
            '--date' => '2026-10-12',
            '--equities-only' => true,
            '--batch' => 1,
        ])->assertSuccessful();

        $rows = EquityPrice::whereDate('traded_date', '2026-10-12')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertSame(10.0, (float) $row->nse_chg_1d);
            $this->assertSame(10.0, (float) $row->nse_chg_3d);
            $this->assertSame(10.0, (float) $row->bse_chg_3d);
        }
    }
}
