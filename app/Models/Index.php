<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Index extends Model
{
    use HasFactory;

    protected $primaryKey = 'index_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'index_code',
        'index_name',
        'exchange',
        'category',
    ];

    public function prices()
    {
        return $this->hasMany(IndexPrice::class, 'index_code', 'index_code');
    }
}
