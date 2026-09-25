<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFundamental extends Model
{
    use HasFactory;

    protected $fillable = [
        'equity_id',
        'isin',
        'dataset',
        'statement_type',
        'time_period',
        'payload',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }
}
