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
        // SQLite restriction: Multiple renames must be in separate table calls
        Schema::table('equity_prices', function (Blueprint $table) {
            $table->renameColumn('nse_chg_12m', 'nse_chg_1y');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->renameColumn('bse_chg_12m', 'bse_chg_1y');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            // 3 Year Change
            $table->decimal('nse_chg_3y', 10, 2)->nullable()->after('nse_chg_1y');
            $table->decimal('bse_chg_3y', 10, 2)->nullable()->after('bse_chg_1y');

            // Gap Percentages
            $table->decimal('nse_gap_pct', 10, 2)->nullable()->after('nse_avg_price');
            $table->decimal('bse_gap_pct', 10, 2)->nullable()->after('bse_avg_price');

            // Intraday Changes (Close vs Open)
            $table->decimal('nse_intraday_chg_pct', 10, 2)->nullable()->after('nse_gap_pct');
            $table->decimal('bse_intraday_chg_pct', 10, 2)->nullable()->after('bse_gap_pct');

            // Daily Range Percentages ((High-Low)/PrevClose)
            $table->decimal('nse_range_pct', 10, 2)->nullable()->after('nse_intraday_chg_pct');
            $table->decimal('bse_range_pct', 10, 2)->nullable()->after('bse_intraday_chg_pct');

            // Avg Ticket Size (Turnover/Trades)
            $table->decimal('nse_avg_ticket_size', 20, 2)->nullable()->after('nse_range_pct');
            $table->decimal('bse_avg_ticket_size', 20, 2)->nullable()->after('bse_range_pct');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('equity_prices', function (Blueprint $table) {
            $table->renameColumn('nse_chg_1y', 'nse_chg_12m');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->renameColumn('bse_chg_1y', 'bse_chg_12m');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->dropColumn([
                'nse_chg_3y',
                'bse_chg_3y',
                'nse_gap_pct',
                'bse_gap_pct',
                'nse_intraday_chg_pct',
                'bse_intraday_chg_pct',
                'nse_range_pct',
                'bse_range_pct',
                'nse_avg_ticket_size',
                'bse_avg_ticket_size'
            ]);
        });
    }
};
