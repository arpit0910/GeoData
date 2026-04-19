<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfNavHistory extends Model
{
    protected $table      = 'mutual_fund_prices';
    public    $timestamps = false;

    protected $fillable = ['isin', 'nav_date', 'nav'];

    protected $casts = ['nav_date' => 'date', 'nav' => 'decimal:4'];

    public function scheme()
    {
        return $this->belongsTo(MfMaster::class, 'isin', 'isin');
    }
}
