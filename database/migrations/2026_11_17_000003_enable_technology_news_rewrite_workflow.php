<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->boolean('allow_ai_rewrite')->default(false);
        });
        // Enable adaptations only for the two reviewed default publishers.
        DB::table('news_sources')->where('name', 'NASA Technology')
            ->where('feed_url', 'https://www.nasa.gov/technology/feed/')
            ->whereNotNull('permissions_verified_at')->update(['allow_ai_rewrite' => true, 'etag' => null, 'last_modified' => null]);
        DB::table('news_sources')->where('name', 'Mozilla Blog')
            ->where('feed_url', 'https://blog.mozilla.org/en/feed/')
            ->whereNotNull('permissions_verified_at')->update(['allow_ai_rewrite' => true, 'etag' => null, 'last_modified' => null]);
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropColumn('allow_ai_rewrite');
        });
    }
};
