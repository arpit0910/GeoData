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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('iso3', 3)->nullable()->index();
            $table->string('iso2', 2)->nullable()->index();
            $table->string('numeric_code', 3)->nullable()->index();
            $table->string('phonecode')->nullable();
            $table->string('capital')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('tld')->nullable();
            $table->string('native')->nullable();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->foreign('region_id')->references('id')->on('regions');
            $table->unsignedBigInteger('subregion_id')->nullable()->index();
            $table->foreign('subregion_id')->references('id')->on('sub_regions');
            $table->string('nationality')->nullable();
            $table->decimal('area_sq_km', 15, 2)->nullable();
            $table->string('postal_code_format')->nullable();
            $table->string('postal_code_regex')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('emoji')->nullable();
            $table->string('emojiU')->nullable();
            $table->string('wiki_data_id')->nullable();
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
        Schema::dropIfExists('countries');
    }
};
