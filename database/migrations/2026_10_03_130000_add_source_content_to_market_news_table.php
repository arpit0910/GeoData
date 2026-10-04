<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->mediumText('original_content')->nullable()->after('original_summary');
            $table->dateTime('source_fetched_at')->nullable()->after('original_content');
            $table->unsignedSmallInteger('rewrite_version')->nullable()->after('rewrite_model');
        });
    }

    public function down(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->dropColumn(['original_content', 'source_fetched_at', 'rewrite_version']);
        });
    }
};
