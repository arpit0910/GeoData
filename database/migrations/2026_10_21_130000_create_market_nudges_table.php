<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_nudges', function (Blueprint $table) {
            $table->id();
            $table->string('source', 16)->index();
            $table->string('source_key', 191);
            $table->string('rule', 40)->index();
            $table->string('severity', 20)->default('notable');
            $table->string('title', 255);
            $table->text('message');
            $table->json('metrics')->nullable();
            $table->timestamp('event_at')->index();
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();

            $table->index(['source', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_nudges');
    }
};
