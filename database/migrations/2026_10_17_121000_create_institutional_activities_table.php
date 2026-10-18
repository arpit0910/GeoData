<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_activities', function (Blueprint $table) {
            $table->id();
            $table->string('investor_type', 10)->index();
            $table->string('data_type', 50)->index();
            $table->string('interval', 5)->index();
            $table->timestamp('activity_at')->index();
            $table->decimal('buy_amount', 24, 4)->nullable();
            $table->decimal('sell_amount', 24, 4)->nullable();
            $table->unsignedBigInteger('buy_contracts')->nullable();
            $table->unsignedBigInteger('sell_contracts')->nullable();
            $table->unsignedBigInteger('oi_contracts')->nullable();
            $table->decimal('oi_amount', 24, 4)->nullable();
            $table->unsignedBigInteger('total_long_contracts')->nullable();
            $table->unsignedBigInteger('total_short_contracts')->nullable();
            $table->unsignedBigInteger('total_call_long_contracts')->nullable();
            $table->unsignedBigInteger('total_put_long_contracts')->nullable();
            $table->unsignedBigInteger('total_call_short_contracts')->nullable();
            $table->unsignedBigInteger('total_put_short_contracts')->nullable();
            $table->timestamp('synced_at')->index();
            $table->timestamps();

            $table->unique(
                ['investor_type', 'data_type', 'interval', 'activity_at'],
                'institutional_activity_record_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_activities');
    }
};
