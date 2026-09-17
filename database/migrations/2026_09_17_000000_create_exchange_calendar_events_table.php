<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('exchange', 8);
            $table->string('segment', 32)->default('equity');
            $table->date('event_date');
            $table->string('name');
            $table->string('event_type', 20)->default('holiday');
            $table->time('session_start')->nullable();
            $table->time('session_end')->nullable();
            $table->string('source', 20);
            $table->text('source_url');
            $table->json('source_payload')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['exchange', 'segment', 'event_date']);
            $table->index(['event_date', 'exchange']);
            $table->index(['exchange', 'event_type', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_calendar_events');
    }
};
