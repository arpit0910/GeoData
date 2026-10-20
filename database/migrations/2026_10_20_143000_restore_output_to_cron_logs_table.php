<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('cron_logs', 'output')) {
                $table->text('output')->nullable()->after('exit_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cron_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('cron_logs', 'output')) {
                $table->dropColumn('output');
            }
        });
    }
};
