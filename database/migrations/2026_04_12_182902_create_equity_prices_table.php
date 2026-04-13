<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equity_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equity_id')->index();
            $table->string('isin')->index();
            $table->date('traded_date')->index();

            // NSE Data
            $table->decimal('nse_open', 15, 2)->nullable();
            $table->decimal('nse_high', 15, 2)->nullable();
            $table->decimal('nse_low', 15, 2)->nullable();
            $table->decimal('nse_close', 15, 2)->nullable();
            $table->decimal('nse_last', 15, 2)->nullable();
            $table->decimal('nse_prev_close', 15, 2)->nullable();
            $table->bigInteger('nse_volume')->nullable();
            $table->decimal('nse_turnover', 20, 2)->nullable();
            $table->bigInteger('nse_trades')->nullable();
            $table->decimal('nse_avg_price', 15, 2)->nullable();

            // BSE Data
            $table->decimal('bse_open', 15, 2)->nullable();
            $table->decimal('bse_high', 15, 2)->nullable();
            $table->decimal('bse_low', 15, 2)->nullable();
            $table->decimal('bse_close', 15, 2)->nullable();
            $table->decimal('bse_last', 15, 2)->nullable();
            $table->decimal('bse_prev_close', 15, 2)->nullable();
            $table->bigInteger('bse_volume')->nullable();
            $table->decimal('bse_turnover', 20, 2)->nullable();
            $table->bigInteger('bse_trades')->nullable();
            $table->decimal('bse_avg_price', 15, 2)->nullable();

            // Performance Metrics (Percentage Change)
            $table->decimal('nse_chg_1d', 10, 2)->nullable();
            $table->decimal('nse_chg_3d', 10, 2)->nullable();
            $table->decimal('nse_chg_7d', 10, 2)->nullable();
            $table->decimal('nse_chg_1m', 10, 2)->nullable();
            $table->decimal('nse_chg_3m', 10, 2)->nullable();
            $table->decimal('nse_chg_6m', 10, 2)->nullable();
            $table->decimal('nse_chg_9m', 10, 2)->nullable();
            $table->decimal('nse_chg_12m', 10, 2)->nullable();

            $table->decimal('bse_chg_1d', 10, 2)->nullable();
            $table->decimal('bse_chg_3d', 10, 2)->nullable();
            $table->decimal('bse_chg_7d', 10, 2)->nullable();
            $table->decimal('bse_chg_1m', 10, 2)->nullable();
            $table->decimal('bse_chg_3m', 10, 2)->nullable();
            $table->decimal('bse_chg_6m', 10, 2)->nullable();
            $table->decimal('bse_chg_9m', 10, 2)->nullable();
            $table->decimal('bse_chg_12m', 10, 2)->nullable();

            $table->decimal('spread', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['isin', 'traded_date']);

            $table->foreign('equity_id')->references('id')->on('equities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equity_prices');
    }
};
