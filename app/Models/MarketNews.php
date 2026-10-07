<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketNews extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    protected $table = 'market_news';

    /** Provider metadata is internal and must never be serialized to customers. */
    protected $hidden = [
        'instrument_key',
        'source',
        'raw_data',
        'original_title',
        'original_summary',
        'original_content',
        'source_fetched_at',
        'source_hash',
        'editorial_status',
        'is_published',
        'rewrite_model',
        'rewrite_version',
        'rewrite_error',
        'rewritten_at',
        'reviewed_at', // Legacy database columns; no review workflow is exposed.
        'reviewed_by',
    ];

    protected $fillable = [
        'isin',
        'symbol',
        'instrument_key',
        'title',
        'summary',
        'thumbnail',
        'article_url',
        'source',
        'published_at',
        'raw_data',
        'original_title',
        'original_summary',
        'original_content',
        'source_fetched_at',
        'source_hash',
        'editorial_status',
        'is_published',
        'rewrite_model',
        'rewrite_version',
        'rewrite_error',
        'rewritten_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'raw_data' => 'array',
        'is_published' => 'boolean',
        'rewritten_at' => 'datetime',
        'source_fetched_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class, 'isin', 'isin');
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id')->limit($limit);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('editorial_status', self::STATUS_PUBLISHED);
    }

    public function scopeForIsin(Builder $query, string $isin): Builder
    {
        return $query->where('isin', strtoupper(trim($isin)));
    }
}
