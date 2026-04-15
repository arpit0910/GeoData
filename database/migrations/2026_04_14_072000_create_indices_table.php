<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indices', function (Blueprint $table) {
            $table->string('index_code')->primary(); // e.g. NIFTY_50
            $table->string('index_name');
            $table->string('exchange')->default('NSE'); // NSE, BSE
            $table->string('category')->nullable(); // Broad-based, Sectoral, Thematic
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indices');
    }
};
