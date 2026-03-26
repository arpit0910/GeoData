<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('total_credits')->default(0)->after('status');
            $table->unsignedBigInteger('used_credits')->default(0)->after('total_credits');
            $table->unsignedBigInteger('available_credits')->default(0)->after('used_credits');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['total_credits', 'used_credits', 'available_credits']);
        });
    }
};
