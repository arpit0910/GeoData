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
            $table->boolean('allow_feed_content')->default(false);
        });
        foreach (['NASA Technology' => 'https://www.nasa.gov/technology/feed/', 'Mozilla Blog' => 'https://blog.mozilla.org/en/feed/'] as $name => $url) {
            DB::table('news_sources')->where('name', $name)->where('feed_url', $url)->where('allow_ai_rewrite', true)
                ->whereNotNull('permissions_verified_at')->update(['allow_feed_content' => true, 'etag' => null, 'last_modified' => null]);
        }
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropColumn('allow_feed_content');
        });
    }
};
