<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('symbol', 50)->nullable()->index();
            $table->string('name');
            $table->string('status', 20)->index();
            $table->string('isin', 20)->nullable()->index();
            $table->string('issue_type', 20)->nullable()->index();
            $table->decimal('issue_size', 18, 4)->nullable();
            $table->string('industry')->nullable()->index();
            $table->decimal('minimum_price', 18, 4)->nullable();
            $table->decimal('maximum_price', 18, 4)->nullable();
            $table->date('bidding_start_date')->nullable()->index();
            $table->date('bidding_end_date')->nullable()->index();
            $table->decimal('total_subscription', 18, 4)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('synced_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipos');
    }
};
