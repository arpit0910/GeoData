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
        Schema::table('indices_prices', function (Blueprint $table) {
            $table->decimal('val_1d', 10, 2)->nullable()->after('div_yield');
            $table->decimal('val_3d', 10, 2)->nullable()->after('val_1d');
            $table->decimal('val_7d', 10, 2)->nullable()->after('val_3d');
            $table->decimal('val_1m', 10, 2)->nullable()->after('val_7d');
            $table->decimal('val_3m', 10, 2)->nullable()->after('val_1m');
            $table->decimal('val_6m', 10, 2)->nullable()->after('val_3m');
            $table->decimal('val_9m', 10, 2)->nullable()->after('val_6m');
            $table->decimal('val_1y', 10, 2)->nullable()->after('val_9m');
            $table->decimal('val_3y', 10, 2)->nullable()->after('val_1y');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->decimal('nse_val_1d', 20, 2)->nullable()->after('nse_avg_price');
            $table->decimal('nse_val_3d', 20, 2)->nullable()->after('nse_val_1d');
            $table->decimal('nse_val_7d', 20, 2)->nullable()->after('nse_val_3d');
            $table->decimal('nse_val_1m', 20, 2)->nullable()->after('nse_val_7d');
            $table->decimal('nse_val_3m', 20, 2)->nullable()->after('nse_val_1m');
            $table->decimal('nse_val_6m', 20, 2)->nullable()->after('nse_val_3m');
            $table->decimal('nse_val_9m', 20, 2)->nullable()->after('nse_val_6m');
            $table->decimal('nse_val_1y', 20, 2)->nullable()->after('nse_val_9m');
            $table->decimal('nse_val_3y', 20, 2)->nullable()->after('nse_val_1y');

            $table->decimal('bse_val_1d', 20, 2)->nullable()->after('bse_avg_price');
            $table->decimal('bse_val_3d', 20, 2)->nullable()->after('bse_val_1d');
            $table->decimal('bse_val_7d', 20, 2)->nullable()->after('bse_val_3d');
            $table->decimal('bse_val_1m', 20, 2)->nullable()->after('bse_val_7d');
            $table->decimal('bse_val_3m', 20, 2)->nullable()->after('bse_val_1m');
            $table->decimal('bse_val_6m', 20, 2)->nullable()->after('bse_val_3m');
            $table->decimal('bse_val_9m', 20, 2)->nullable()->after('bse_val_6m');
            $table->decimal('bse_val_1y', 20, 2)->nullable()->after('bse_val_9m');
            $table->decimal('bse_val_3y', 20, 2)->nullable()->after('bse_val_1y');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indices_prices', function (Blueprint $table) {
            $table->dropColumn([
                'val_1d', 'val_3d', 'val_7d', 'val_1m', 'val_3m', 'val_6m', 'val_9m', 'val_1y', 'val_3y'
            ]);
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->dropColumn([
                'nse_val_1d', 'nse_val_3d', 'nse_val_7d', 'nse_val_1m', 'nse_val_3m', 'nse_val_6m', 'nse_val_9m', 'nse_val_1y', 'nse_val_3y',
                'bse_val_1d', 'bse_val_3d', 'bse_val_7d', 'bse_val_1m', 'bse_val_3m', 'bse_val_6m', 'bse_val_9m', 'bse_val_1y', 'bse_val_3y'
            ]);
        });
    }
};
