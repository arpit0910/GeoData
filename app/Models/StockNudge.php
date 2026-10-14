<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNudge extends Model
{
    use HasFactory;

    public const CATEGORY_RULES = [
        'growth' => ['daily_gain', 'month_high', 'year_high'],
        'fall' => ['daily_drop', 'month_low', 'year_low'],
        'volume' => ['volume_spike'],
        'comparison' => ['index_outperformance', 'index_underperformance'],
    ];

    protected $fillable = [
        'equity_id', 'isin', 'index_code', 'exchange', 'type', 'rule', 'severity',
        'title', 'message', 'observed_price', 'metrics', 'trading_date',
        'observed_at', 'expires_at', 'fingerprint',
    ];

    protected $casts = [
        'metrics' => 'array',
        'trading_date' => 'date',
        'observed_at' => 'datetime',
        'expires_at' => 'datetime',
        'observed_price' => 'decimal:4',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }

    public function index()
    {
        return $this->belongsTo(Index::class, 'index_code', 'index_code');
    }

    public static function rulesForCategory(string $category): ?array
    {
        return self::CATEGORY_RULES[$category] ?? null;
    }

    public static function categoryForRule(string $rule): ?string
    {
        foreach (self::CATEGORY_RULES as $category => $rules) {
            if (in_array($rule, $rules, true)) {
                return $category;
            }
        }

        return null;
    }
}
