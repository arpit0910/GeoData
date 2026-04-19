<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfMaster extends Model
{
    protected $table      = 'mutual_funds';
    protected $primaryKey = 'isin';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'isin', 'scheme_code', 'isin_reinvest', 'scheme_name',
        'amc_name', 'category', 'sub_category', 'type', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function navHistory()
    {
        return $this->hasMany(MfNavHistory::class, 'isin', 'isin');
    }

    public function latestNav()
    {
        return $this->hasOne(MfNavHistory::class, 'isin', 'isin')
                    ->latestOfMany('nav_date');
    }
}
