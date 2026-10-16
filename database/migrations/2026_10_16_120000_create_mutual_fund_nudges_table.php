<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutual_fund_nudges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutual_fund_id')->constrained('mutual_funds')->cascadeOnDelete();
            $table->string('isin', 12);
            $table->string('rule', 50);
            $table->string('severity', 20)->default('info');
            $table->string('title');
            $table->text('message');
            $table->decimal('observed_nav', 15, 4);
            $table->json('metrics')->nullable();
            $table->date('nav_date');
            $table->timestamp('observed_at');
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();

            $table->index(['isin', 'nav_date']);
            $table->index(['rule', 'nav_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutual_fund_nudges');
    }
};
