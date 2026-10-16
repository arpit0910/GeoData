<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutualFundNudge extends Model
{
    use HasFactory;

    public const CATEGORY_RULES = [
        'growth' => ['daily_gain', 'month_high', 'year_high'],
        'fall' => ['daily_drop', 'month_low', 'year_low'],
    ];

    protected $fillable = [
        'mutual_fund_id', 'isin', 'rule', 'severity', 'title', 'message',
        'observed_nav', 'metrics', 'nav_date', 'observed_at', 'fingerprint',
    ];

    protected $casts = [
        'observed_nav' => 'decimal:4',
        'metrics' => 'array',
        'nav_date' => 'date',
        'observed_at' => 'datetime',
    ];

    public function fund()
    {
        return $this->belongsTo(MfMaster::class, 'mutual_fund_id', 'id');
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
