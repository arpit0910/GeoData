<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indices_prices', function (Blueprint $table) {
            $table->id();
            $table->string('index_code');
            $table->date('traded_date');

            $table->decimal('open', 16, 2)->nullable();
            $table->decimal('high', 16, 2)->nullable();
            $table->decimal('low', 16, 2)->nullable();
            $table->decimal('close', 16, 2)->nullable();
            $table->decimal('prev_close', 16, 2)->nullable();
            $table->decimal('change_percent', 10, 4)->nullable();

            // Liquidity Metrics
            $table->decimal('volume', 20, 2)->nullable();
            $table->decimal('turnover', 20, 2)->nullable(); // In Rs. Cr.

            // Valuation Metrics
            $table->decimal('pe_ratio', 10, 2)->nullable();
            $table->decimal('pb_ratio', 10, 2)->nullable();
            $table->decimal('div_yield', 10, 2)->nullable();
            $table->decimal('val_3d', 10, 2)->nullable();
            $table->decimal('val_7d', 10, 2)->nullable();
            $table->decimal('val_1m', 10, 2)->nullable();
            $table->decimal('val_3m', 10, 2)->nullable();
            $table->decimal('val_6m', 10, 2)->nullable();
            $table->decimal('val_9m', 10, 2)->nullable();
            $table->decimal('val_1y', 10, 2)->nullable();
            $table->decimal('val_3y', 10, 2)->nullable();
            // Analytical Metrics
            $table->decimal('gap_pct', 10, 4)->nullable();
            $table->decimal('intraday_chg_pct', 10, 4)->nullable();
            $table->decimal('range_pct', 10, 4)->nullable();

            // Historical Performance (Returns)
            $table->decimal('chg_1d', 10, 4)->nullable();
            $table->decimal('chg_3d', 10, 4)->nullable();
            $table->decimal('chg_7d', 10, 4)->nullable();
            $table->decimal('chg_1m', 10, 4)->nullable();
            $table->decimal('chg_3m', 10, 4)->nullable();
            $table->decimal('chg_6m', 10, 4)->nullable();
            $table->decimal('chg_9m', 10, 4)->nullable();
            $table->decimal('chg_1y', 10, 4)->nullable();
            $table->decimal('chg_3y', 10, 4)->nullable();


            $table->timestamps();

            $table->foreign('index_code')->references('index_code')->on('indices')->onDelete('cascade');
            $table->unique(['index_code', 'traded_date']);
            $table->index('traded_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indices_prices');
    }
};
