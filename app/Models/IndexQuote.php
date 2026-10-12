<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexQuote extends Model
{
    protected $fillable = [
        'index_code', 'price', 'previous_close', 'change', 'change_percent',
        'volume', 'quoted_at', 'fetched_at', 'payload',
    ];

    protected $casts = [
        'price' => 'float',
        'previous_close' => 'float',
        'change' => 'float',
        'change_percent' => 'float',
        'volume' => 'float',
        'quoted_at' => 'datetime',
        'fetched_at' => 'datetime',
        'payload' => 'array',
    ];

    public function index()
    {
        return $this->belongsTo(Index::class, 'index_code', 'index_code');
    }
}
