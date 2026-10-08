<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->timestamp('corporate_actions_sync_attempted_at')->nullable()->index();
            $table->timestamp('corporate_actions_synced_at')->nullable()->index();
            $table->text('corporate_actions_sync_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->dropIndex(['corporate_actions_sync_attempted_at']);
            $table->dropIndex(['corporate_actions_synced_at']);
            $table->dropColumn([
                'corporate_actions_sync_attempted_at',
                'corporate_actions_synced_at',
                'corporate_actions_sync_error',
            ]);
        });
    }
};
