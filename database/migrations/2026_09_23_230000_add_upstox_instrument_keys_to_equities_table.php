<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->string('upstox_nse_instrument_key', 100)->nullable()->unique()->after('bse_symbol');
            $table->string('upstox_bse_instrument_key', 100)->nullable()->unique()->after('upstox_nse_instrument_key');
        });
    }

    public function down(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->dropUnique(['upstox_nse_instrument_key']);
            $table->dropUnique(['upstox_bse_instrument_key']);
            $table->dropColumn(['upstox_nse_instrument_key', 'upstox_bse_instrument_key']);
        });
    }
};
