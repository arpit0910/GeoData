<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. mutual_funds: Add unique index to ISIN
        Schema::table('mutual_funds', function (Blueprint $table) use ($driver) {
            // For SQLite, we might need to be careful if there are duplicates.
            // But usually this table is populated via sync.
            $table->unique('isin');
        });

        // 2. mutual_fund_prices: Add unique index to [isin, nav_date]
        Schema::table('mutual_fund_prices', function (Blueprint $table) use ($driver) {
            $table->unique(['isin', 'nav_date']);
        });
    }

    public function down(): void
    {
        Schema::table('mutual_fund_prices', function (Blueprint $table) {
            $table->dropUnique(['isin', 'nav_date']);
        });

        Schema::table('mutual_funds', function (Blueprint $table) {
            $table->dropUnique(['isin']);
        });
    }
};
