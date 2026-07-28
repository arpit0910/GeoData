<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('cron_logs', 'source')) {
                $table->string('source')->default('scheduled')->after('ip');
            }

            if (!Schema::hasColumn('cron_logs', 'exit_code')) {
                $table->integer('exit_code')->nullable()->after('status');
            }

            if (!Schema::hasColumn('cron_logs', 'output')) {
                $table->longText('output')->nullable()->after('exit_code');
            }

            if (!Schema::hasColumn('cron_logs', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('output');
            }

            if (!Schema::hasColumn('cron_logs', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cron_logs', function (Blueprint $table) {
            $drops = [];

            foreach (['source', 'exit_code', 'output', 'started_at', 'finished_at'] as $column) {
                if (Schema::hasColumn('cron_logs', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
