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
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->string('zone_name')->nullable();
            $table->string('gmt_offset')->nullable();
            $table->string('gmt_offset_name')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('tz_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timezones');
    }
};
