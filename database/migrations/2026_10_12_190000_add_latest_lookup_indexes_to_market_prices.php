<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equity_prices', function (Blueprint $table) {
            $table->index(['isin', 'id'], 'equity_prices_isin_id_index');
        });

        Schema::table('mutual_fund_prices', function (Blueprint $table) {
            $table->index(['isin', 'id'], 'mutual_fund_prices_isin_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('equity_prices', function (Blueprint $table) {
            $table->dropIndex('equity_prices_isin_id_index');
        });

        Schema::table('mutual_fund_prices', function (Blueprint $table) {
            $table->dropIndex('mutual_fund_prices_isin_id_index');
        });
    }
};
