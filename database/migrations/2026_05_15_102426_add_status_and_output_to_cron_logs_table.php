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
        Schema::table('cron_logs', function (Blueprint $table) {
            $table->boolean('status')->default(true)->after('ip');
        });
    }

    public function down()
    {
        Schema::table('cron_logs', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
};
