<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateAction extends Model
{
    use HasFactory;

    protected $table = 'corporate_actions';

    /** Raw provider responses are internal and must never be serialized to customers. */
    protected $hidden = [
        'raw_data',
    ];

    protected $fillable = [
        'isin',
        'symbol',
        'company_name',
        'type', // 'SPLIT', 'BONUS', 'DIVIDEND', 'RIGHTS', 'EVENT'
        'name',
        'expiry_date',
        'record_date',
        'announcement_date',
        'ratio',
        'amount',
        'details',
        'raw_data',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'record_date' => 'date',
        'announcement_date' => 'date',
        'amount' => 'decimal:4',
        'raw_data' => 'array',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class, 'isin', 'isin');
    }

    public function scopeSplits(Builder $query): Builder
    {
        return $query->where('type', 'SPLIT');
    }

    public function scopeBonuses(Builder $query): Builder
    {
        return $query->where('type', 'BONUS');
    }

    public function scopeDividends(Builder $query): Builder
    {
        return $query->where('type', 'DIVIDEND');
    }

    public function scopeEvents(Builder $query): Builder
    {
        return $query->where('type', 'EVENT');
    }

    public function scopeForIsin(Builder $query, string $isin): Builder
    {
        return $query->where('isin', strtoupper(trim($isin)));
    }
}
