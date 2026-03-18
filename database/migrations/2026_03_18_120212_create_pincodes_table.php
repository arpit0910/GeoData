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
        Schema::create('pincodes', function (Blueprint $table) {
            $table->id();
            $table->string('postal_code');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->string('short_state')->nullable();
            $table->string('county')->nullable();
            $table->string('short_county')->nullable();
            $table->string('community')->nullable();
            $table->string('short_community')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('accuracy')->nullable();
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
        Schema::dropIfExists('pincodes');
    }
};
