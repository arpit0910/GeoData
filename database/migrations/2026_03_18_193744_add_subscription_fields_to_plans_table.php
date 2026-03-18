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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('billing_cycle')->default('monthly')->after('status')->comment('e.g. monthly, yearly, lifetime');
            $table->text('terms')->nullable()->after('billing_cycle');
            $table->json('benefits')->nullable()->after('terms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'terms', 'benefits']);
        });
    }
};
