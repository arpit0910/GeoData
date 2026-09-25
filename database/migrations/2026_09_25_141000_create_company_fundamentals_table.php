<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_fundamentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equity_id')->nullable()->constrained('equities')->nullOnDelete();
            $table->string('isin', 20)->index();
            $table->string('dataset', 50)->index();
            $table->string('statement_type', 20)->default('not_applicable');
            $table->string('time_period', 20)->default('not_applicable');
            $table->json('payload');
            $table->timestamp('synced_at')->index();
            $table->timestamps();

            $table->unique(
                ['isin', 'dataset', 'statement_type', 'time_period'],
                'company_fundamentals_dataset_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fundamentals');
    }
};
