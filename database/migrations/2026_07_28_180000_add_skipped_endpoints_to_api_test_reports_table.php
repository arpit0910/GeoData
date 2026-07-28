<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_test_reports', function (Blueprint $table) {
            $table->unsignedInteger('skipped_endpoints')->default(0)->after('failed_endpoints');
        });
    }

    public function down(): void
    {
        Schema::table('api_test_reports', function (Blueprint $table) {
            $table->dropColumn('skipped_endpoints');
        });
    }
};
