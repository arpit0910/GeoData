<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('market_news')) {
            Schema::create('market_news', function (Blueprint $table) {
                $table->id();
                $table->string('isin', 20)->nullable()->index();
                $table->string('symbol', 50)->nullable()->index();
                $table->string('instrument_key', 100)->nullable()->index();
                $table->string('title', 500);
                $table->text('summary')->nullable();
                $table->string('thumbnail', 1000)->nullable();
                $table->string('article_url', 1000)->nullable();
                $table->string('source', 100)->default('Upstox');
                $table->dateTime('published_at')->nullable()->index();
                $table->json('raw_data')->nullable();
                $table->timestamps();

                $table->index(['isin', 'published_at']);
            });
        }

        if (!Schema::hasTable('corporate_actions')) {
            Schema::create('corporate_actions', function (Blueprint $table) {
                $table->id();
                $table->string('isin', 20)->index();
                $table->string('symbol', 50)->nullable()->index();
                $table->string('company_name', 255)->nullable();
                $table->string('type', 50)->index(); // 'SPLIT', 'BONUS', 'DIVIDEND', 'RIGHTS', 'EVENT'
                $table->string('name', 255);
                $table->date('expiry_date')->nullable()->index(); // ex-date
                $table->date('record_date')->nullable();
                $table->date('announcement_date')->nullable();
                $table->string('ratio', 50)->nullable();
                $table->decimal('amount', 12, 4)->nullable();
                $table->text('details')->nullable();
                $table->json('raw_data')->nullable();
                $table->timestamps();

                $table->index(['type', 'expiry_date']);
                $table->index(['isin', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_actions');
        Schema::dropIfExists('market_news');
    }
};
