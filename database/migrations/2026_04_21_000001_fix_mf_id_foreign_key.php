<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // mf_id was designed to store scheme_code (numeric), not mutual_funds.id (auto-increment).
        // The FK referencing mutual_funds.id was incorrect — drop it.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('mutual_fund_prices', function (Blueprint $table) {
                $table->dropForeign(['mf_id']);
            });
        }

        // Ensure mf_id is populated correctly as scheme_code where missing or wrong.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE mutual_fund_prices
                SET mf_id = (SELECT scheme_code FROM mutual_funds WHERE mutual_funds.isin = mutual_fund_prices.isin)
            ');
        } else {
            DB::statement('
                UPDATE mutual_fund_prices mfp
                JOIN mutual_funds mf ON mf.isin = mfp.isin
                SET mfp.mf_id = CAST(mf.scheme_code AS UNSIGNED)
            ');
        }
    }

    public function down(): void
    {
        Schema::table('mutual_fund_prices', function (Blueprint $table) {
            $table->foreign('mf_id')->references('id')->on('mutual_funds')->onDelete('cascade');
        });
    }
};
