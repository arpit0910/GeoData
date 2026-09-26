<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upstox_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->index();
            $table->text('access_token')->nullable();
            $table->string('token_type', 20)->default('Bearer');
            $table->string('upstox_user_id')->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('renewal_requested_at')->nullable();
            $table->timestamp('authorization_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upstox_access_tokens');
    }
};
