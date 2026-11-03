<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('amfi_id')->unique();
            $table->string('name', 191)->unique();
            $table->string('company_name')->nullable();
            $table->text('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('logo_source_url')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('icon_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amcs');
    }
};
