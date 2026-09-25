<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_key', 150)->unique();
            $table->string('segment', 40)->index();
            $table->string('name');
            $table->string('exchange', 20)->default('GLOBAL')->index();
            $table->string('country', 100)->nullable()->index();
            $table->string('latency', 50)->nullable();
            $table->string('instrument_type', 50)->nullable();
            $table->string('trading_symbol', 100)->nullable()->index();
            $table->string('start_time', 100)->nullable();
            $table->string('end_time', 100)->nullable();
            $table->string('week_days', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('provider_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_instruments');
    }
};
