<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_nudges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equity_id')->nullable()->constrained('equities')->nullOnDelete();
            $table->string('isin', 20)->index();
            $table->string('exchange', 8);
            $table->string('type', 20)->index();
            $table->string('rule', 50)->index();
            $table->string('severity', 20)->default('info');
            $table->string('title', 255);
            $table->text('message');
            $table->decimal('observed_price', 20, 4)->nullable();
            $table->json('metrics')->nullable();
            $table->date('trading_date')->index();
            $table->timestamp('observed_at')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();

            $table->index(['type', 'trading_date', 'severity']);
            $table->index(['isin', 'trading_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_nudges');
    }
};
