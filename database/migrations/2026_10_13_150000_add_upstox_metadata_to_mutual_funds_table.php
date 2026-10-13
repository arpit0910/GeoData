<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutual_funds', function (Blueprint $table) {
            $table->json('upstox_metadata')->nullable()->after('is_active');
            $table->timestamp('upstox_synced_at')->nullable()->after('upstox_metadata');
        });
    }

    public function down(): void
    {
        Schema::table('mutual_funds', function (Blueprint $table) {
            $table->dropColumn(['upstox_metadata', 'upstox_synced_at']);
        });
    }
};
