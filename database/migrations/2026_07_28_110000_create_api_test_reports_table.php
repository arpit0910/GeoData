<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('mode', 20)->default('demo');
            $table->string('status', 20)->default('completed');
            $table->string('report_name');
            $table->unsignedInteger('total_endpoints')->default(0);
            $table->unsignedInteger('passed_endpoints')->default(0);
            $table->unsignedInteger('failed_endpoints')->default(0);
            $table->decimal('average_duration_ms', 10, 2)->default(0);
            $table->json('selected_endpoints')->nullable();
            $table->json('summary')->nullable();
            $table->json('results')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_test_reports');
    }
};
