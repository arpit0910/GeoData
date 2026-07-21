<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indices_prices', function (Blueprint $table) {
            $table->json('holdings')->nullable()->after('overview');
        });
    }

    public function down(): void
    {
        Schema::table('indices_prices', function (Blueprint $table) {
            $table->dropColumn('holdings');
        });
    }
};
