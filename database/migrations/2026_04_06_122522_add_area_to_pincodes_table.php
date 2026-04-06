<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pincodes', function (Blueprint $table) {
            // 'area' stores the locality/neighbourhood from the CSV 'city' column
            // The actual city link is city_id (resolved from county/community columns)
            $table->string('area')->nullable()->after('city_id');
        });
    }

    public function down()
    {
        Schema::table('pincodes', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
