<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Populate mf_id with the numeric scheme_code from mutual_funds via isin.
        // (isin, nav_date) is already the composite PK so no unique index is needed.
        DB::statement('
            UPDATE mutual_fund_prices
            SET mf_id = CAST((
                SELECT scheme_code FROM mutual_funds
                WHERE mutual_funds.isin = mutual_fund_prices.isin
                LIMIT 1
            ) AS INTEGER)
            WHERE mf_id IS NULL
        ');
    }

    public function down(): void
    {
        DB::table('mutual_fund_prices')->update(['mf_id' => null]);
    }
};
