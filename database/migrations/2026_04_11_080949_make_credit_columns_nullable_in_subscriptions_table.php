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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('total_credits')->nullable()->change();
            $table->unsignedBigInteger('used_credits')->nullable()->change();
            $table->unsignedBigInteger('available_credits')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('total_credits')->nullable(false)->change();
            $table->unsignedBigInteger('used_credits')->nullable(false)->change();
            $table->unsignedBigInteger('available_credits')->nullable(false)->change();
        });
    }
};
