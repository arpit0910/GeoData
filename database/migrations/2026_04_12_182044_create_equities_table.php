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
        Schema::create('equities', function (Blueprint $table) {
            $table->id();
            $table->string('isin')->unique();
            $table->string('company_name')->nullable();
            $table->string('nse_symbol')->nullable()->index();
            $table->string('bse_symbol')->nullable()->index();
            $table->string('industry')->nullable();
            $table->decimal('face_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('equities');
    }
};
