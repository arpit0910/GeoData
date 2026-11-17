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
            $table->string('feed_content_field', 20)->default('content');
            $table->string('license_name', 120)->nullable();
            $table->string('license_url', 2048)->nullable();
        });
        foreach ([
            'https://blog.mozilla.org/en/feed/' => ['CC BY-SA 3.0 or later', 'https://creativecommons.org/licenses/by-sa/3.0/'],
            'https://www.nasa.gov/technology/feed/' => ['NASA editorial-use guidelines', 'https://www.nasa.gov/nasa-brand-center/images-and-media/'],
        ] as $feed => [$name, $url]) {
            DB::table('news_sources')->where('feed_url', $feed)->whereNull('license_name')
                ->update(['license_name' => $name, 'license_url' => $url]);
        }
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropColumn(['feed_content_field', 'license_name', 'license_url']);
        });
    }
};
