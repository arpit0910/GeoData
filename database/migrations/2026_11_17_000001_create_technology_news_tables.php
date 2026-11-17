<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_jobs', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->string('queue')->index(); $t->longText('payload');
            $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at');
        });
        Schema::create('news_sources', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('source_type', 20);
            $t->string('base_url', 2048); $t->text('feed_url')->nullable(); $t->text('api_endpoint')->nullable();
            $t->boolean('is_active')->default(false); $t->unsignedInteger('sync_interval_minutes')->default(60);
            $t->string('display_mode', 20)->default('disabled');
            $t->text('permission_reference')->nullable(); $t->text('permission_notes')->nullable();
            $t->timestamp('permissions_verified_at')->nullable();
            $t->timestamp('last_sync_at')->nullable(); $t->timestamp('last_success_at')->nullable();
            $t->timestamp('retry_after_at')->nullable(); $t->text('last_error')->nullable();
            $t->unsignedInteger('consecutive_failures')->default(0);
            $t->string('etag')->nullable(); $t->string('last_modified')->nullable(); $t->timestamps();
        });
        Schema::create('news_categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->text('description')->nullable();
            $t->boolean('is_active')->default(true); $t->unsignedInteger('sort_order')->default(0); $t->timestamps();
        });
        Schema::create('news_articles', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained('news_sources');
            $t->string('source_article_id', 191); $t->text('title'); $t->string('slug')->unique();
            $t->text('description')->nullable(); $t->longText('content')->nullable();
            $t->text('original_url'); $t->text('canonical_url'); $t->char('url_hash', 64)->unique();
            $t->string('author_name')->nullable(); $t->string('publisher_name');
            $t->text('image_url')->nullable(); $t->string('image_alt')->nullable();
            $t->foreignId('category_id')->constrained('news_categories'); $t->json('tags')->nullable();
            $t->string('language', 20)->nullable(); $t->timestamp('published_at')->index();
            $t->timestamp('fetched_at'); $t->timestamp('last_seen_at');
            $t->string('status', 20)->default('published')->index();
            $t->boolean('is_featured')->default(false); $t->boolean('is_trending')->default(false);
            $t->unsignedInteger('source_score')->nullable();
            $t->char('content_hash', 64)->index(); $t->char('title_hash', 64)->index();
            $t->boolean('editorial_category')->default(false); $t->timestamps();
            $t->unique(['source_id', 'source_article_id']);
            $t->index(['status', 'published_at', 'id']); $t->index(['category_id', 'published_at']);
        });
        Schema::create('news_article_references', function (Blueprint $t) {
            $t->id(); $t->foreignId('article_id')->constrained('news_articles')->cascadeOnDelete();
            $t->foreignId('source_id')->constrained('news_sources'); $t->string('source_article_id', 191);
            $t->timestamp('last_seen_at'); $t->unique(['source_id', 'source_article_id']);
        });
        Schema::create('news_duplicate_candidates', function (Blueprint $t) {
            $t->id(); $t->foreignId('article_id')->constrained('news_articles')->cascadeOnDelete();
            $t->foreignId('candidate_id')->constrained('news_articles')->cascadeOnDelete();
            $t->string('reason'); $t->string('status', 20)->default('pending'); $t->timestamps();
            $t->unique(['article_id', 'candidate_id']);
        });
        Schema::create('news_sync_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained('news_sources');
            $t->timestamp('started_at'); $t->timestamp('completed_at')->nullable();
            $t->string('status', 20)->index();
            foreach (['fetched', 'inserted', 'updated', 'duplicate', 'skipped', 'failed'] as $counter) {
                $t->unsignedInteger($counter.'_count')->default(0);
            }
            $t->text('error_message')->nullable(); $t->unsignedInteger('duration_ms')->nullable(); $t->timestamp('created_at')->nullable();
        });
        // A database lease coordinates CLI, web workers, and multiple application servers.
        Schema::create('news_sync_locks', function (Blueprint $t) {
            $t->string('name')->primary(); $t->string('owner', 64); $t->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        foreach (['news_jobs', 'news_sync_locks', 'news_sync_logs', 'news_duplicate_candidates', 'news_article_references', 'news_articles', 'news_categories', 'news_sources'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
