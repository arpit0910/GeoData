<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketNews extends Model
{
    use HasFactory;

    protected $table = 'market_news';

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
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class, 'isin', 'isin');
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id')->limit($limit);
    }

    public function scopeForIsin(Builder $query, string $isin): Builder
    {
        return $query->where('isin', strtoupper(trim($isin)));
    }
}
