<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indices', function (Blueprint $table) {
            $table->string('upstox_instrument_key')->nullable()->unique()->after('category');
            $table->json('upstox_metadata')->nullable()->after('upstox_instrument_key');
            $table->timestamp('upstox_synced_at')->nullable()->after('upstox_metadata');
        });

        Schema::create('index_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('index_code')->unique();
            $table->decimal('price', 20, 4);
            $table->decimal('previous_close', 20, 4)->nullable();
            $table->decimal('change', 20, 4)->nullable();
            $table->decimal('change_percent', 10, 4)->nullable();
            $table->decimal('volume', 24, 2)->nullable();
            $table->timestamp('quoted_at');
            $table->timestamp('fetched_at')->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('index_code')->references('index_code')->on('indices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_quotes');
        Schema::table('indices', function (Blueprint $table) {
            $table->dropUnique(['upstox_instrument_key']);
            $table->dropColumn(['upstox_instrument_key', 'upstox_metadata', 'upstox_synced_at']);
        });
    }
};
