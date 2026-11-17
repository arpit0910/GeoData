<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            // Provider metadata remains in title/description/content/tags.
            // Generated copy is stored independently and published explicitly.
            $table->text('ai_title')->nullable();
            $table->text('ai_summary')->nullable();
            $table->longText('ai_content')->nullable();
            $table->json('ai_tags')->nullable();
            $table->string('ai_meta_title')->nullable();
            $table->text('ai_meta_description')->nullable();
            $table->string('editorial_status', 20)->default('pending')->index();
            $table->string('rewrite_model', 100)->nullable();
            $table->unsignedSmallInteger('rewrite_version')->nullable();
            $table->char('rewrite_source_hash', 64)->nullable();
            $table->text('rewrite_error')->nullable();
            $table->dateTime('rewrite_retry_at')->nullable()->index();
            $table->dateTime('rewritten_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropIndex(['editorial_status']);
            $table->dropIndex(['rewrite_retry_at']);
            $table->dropColumn([
                'ai_title', 'ai_summary', 'ai_content', 'ai_tags',
                'ai_meta_title', 'ai_meta_description', 'editorial_status',
                'rewrite_model', 'rewrite_version', 'rewrite_source_hash',
                'rewrite_error', 'rewrite_retry_at', 'rewritten_at',
            ]);
        });
    }
};
