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
            $table->decimal('chg_9m', 10, 4)->nullable()->after('chg_6m');
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
            $table->dropColumn('chg_9m');
        });
    }
};
