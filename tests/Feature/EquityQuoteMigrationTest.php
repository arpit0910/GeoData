<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquityQuoteMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_quote_backfill_is_resumable_and_repairs_legacy_zero_dates(): void
    {
        DB::table('equity_quotes')->insert([
            'isin' => 'INE540P07244',
            'exchange' => 'BSE',
            'symbol' => '975UPPCL25.BO',
            'price' => 1000000,
            'quoted_at' => '0000-00-00 00:00:00',
            'fetched_at' => '2026-09-17 05:01:22',
            'payload' => json_encode([
                'symbol' => '975UPPCL25.BO',
                'price' => 1000000,
                'quoted_at' => '1970-01-01T00:00:00+00:00',
                'fetched_at' => '2026-09-17T05:01:22+00:00',
                'source' => 'live',
            ], JSON_THROW_ON_ERROR),
        ]);

        $migration = require database_path(
            'migrations/2026_09_28_130000_store_nse_and_bse_in_single_equity_quote.php'
        );
        $migration->up();

        $row = DB::table('equity_quotes')->where('isin', 'INE540P07244')->first();
        $this->assertSame('2026-09-17 05:01:22', $row->quoted_at);
        $this->assertSame('2026-09-17 05:01:22', $row->bse_quoted_at);
        $this->assertSame('975UPPCL25.BO', $row->bse_symbol);
        $this->assertSame(1000000.0, (float) $row->bse_price);
    }
}
