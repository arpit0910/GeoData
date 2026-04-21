<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('ip')->nullable();
            $table->timestamp('ran_at');
            $table->timestamps();

            $table->index('title');
            $table->index('ran_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_logs');
    }
};
