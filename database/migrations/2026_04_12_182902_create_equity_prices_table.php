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
            $table->decimal('nse_chg_1y', 10, 2)->nullable();
            $table->decimal('nse_chg_3y', 10, 2)->nullable();

            $table->decimal('nse_val_1d', 20, 2)->nullable();
            $table->decimal('nse_val_3d', 20, 2)->nullable();
            $table->decimal('nse_val_7d', 20, 2)->nullable();
            $table->decimal('nse_val_1m', 20, 2)->nullable();
            $table->decimal('nse_val_3m', 20, 2)->nullable();
            $table->decimal('nse_val_6m', 20, 2)->nullable();
            $table->decimal('nse_val_9m', 20, 2)->nullable();
            $table->decimal('nse_val_1y', 20, 2)->nullable();
            $table->decimal('nse_val_3y', 20, 2)->nullable();

            $table->decimal('bse_chg_1d', 10, 2)->nullable();
            $table->decimal('bse_chg_3d', 10, 2)->nullable();
            $table->decimal('bse_chg_7d', 10, 2)->nullable();
            $table->decimal('bse_chg_1m', 10, 2)->nullable();
            $table->decimal('bse_chg_3m', 10, 2)->nullable();
            $table->decimal('bse_chg_6m', 10, 2)->nullable();
            $table->decimal('bse_chg_9m', 10, 2)->nullable();
            $table->decimal('bse_chg_1y', 10, 2)->nullable();
            $table->decimal('bse_chg_3y', 10, 2)->nullable();
            
            $table->decimal('bse_val_1d', 20, 2)->nullable();
            $table->decimal('bse_val_3d', 20, 2)->nullable();
            $table->decimal('bse_val_7d', 20, 2)->nullable();
            $table->decimal('bse_val_1m', 20, 2)->nullable();
            $table->decimal('bse_val_3m', 20, 2)->nullable();
            $table->decimal('bse_val_6m', 20, 2)->nullable();
            $table->decimal('bse_val_9m', 20, 2)->nullable();
            $table->decimal('bse_val_1y', 20, 2)->nullable();
            $table->decimal('bse_val_3y', 20, 2)->nullable();

            $table->decimal('spread', 15, 2)->nullable();

            // Gap Percentages
            $table->decimal('nse_gap_pct', 10, 2)->nullable();
            $table->decimal('bse_gap_pct', 10, 2)->nullable();

            // Intraday Changes (Close vs Open)
            $table->decimal('nse_intraday_chg_pct', 10, 2)->nullable();
            $table->decimal('bse_intraday_chg_pct', 10, 2)->nullable();

            // Daily Range Percentages ((High-Low)/PrevClose)
            $table->decimal('nse_range_pct', 10, 2)->nullable();
            $table->decimal('bse_range_pct', 10, 2)->nullable();

            // Avg Ticket Size (Turnover/Trades)
            $table->decimal('nse_avg_ticket_size', 20, 2)->nullable();
            $table->decimal('bse_avg_ticket_size', 20, 2)->nullable();

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
