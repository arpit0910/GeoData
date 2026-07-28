<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_logs', function (Blueprint $table) {
            if (Schema::hasColumn('cron_logs', 'output')) {
                $table->dropColumn('output');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cron_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('cron_logs', 'output')) {
                $table->longText('output')->nullable()->after('exit_code');
            }
        });
    }
};
