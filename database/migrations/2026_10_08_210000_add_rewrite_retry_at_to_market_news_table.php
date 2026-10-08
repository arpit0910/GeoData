<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->dateTime('rewrite_retry_at')->nullable()->index()->after('rewrite_error');
        });
    }

    public function down(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->dropColumn('rewrite_retry_at');
        });
    }
};
