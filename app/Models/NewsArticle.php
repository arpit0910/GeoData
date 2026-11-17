<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    use \App\Models\Concerns\UsesUtcTimestamps;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    protected $guarded = ['id'];
    protected $hidden = [
        'content', 'content_hash', 'title_hash', 'url_hash', 'editorial_category',
        'ai_title', 'ai_summary', 'ai_content', 'ai_tags', 'ai_meta_title',
        'ai_meta_description', 'editorial_status', 'rewrite_model', 'rewrite_version',
        'rewrite_source_hash', 'rewrite_error', 'rewrite_retry_at', 'rewritten_at',
    ];
    protected $casts = [
        'tags' => 'array', 'published_at' => 'datetime', 'fetched_at' => 'datetime',
        'last_seen_at' => 'datetime', 'is_featured' => 'boolean', 'is_trending' => 'boolean',
        'editorial_category' => 'boolean', 'ai_tags' => 'array', 'rewrite_version' => 'integer',
        'rewrite_retry_at' => 'datetime', 'rewritten_at' => 'datetime',
    ];
    public function source() { return $this->belongsTo(NewsSource::class); }
    public function category() { return $this->belongsTo(NewsCategory::class); }
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('editorial_status', self::STATUS_PUBLISHED)
            ->whereNotNull('rewritten_at')->whereNotNull('ai_title')->whereNotNull('ai_summary')
            ->whereNotNull('ai_content')->where('rewrite_version', '>=', 2)
            ->whereColumn('rewrite_source_hash', 'content_hash')->where('published_at', '<=', now('UTC'))
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->whereHas('source', fn ($q) => $q->where('is_active', true)->where('allow_ai_rewrite', true)->where('allow_feed_content', true)->whereNotNull('permissions_verified_at')->whereNotNull('permission_reference')->whereIn('display_mode', ['headline', 'excerpt']));
    }
}
