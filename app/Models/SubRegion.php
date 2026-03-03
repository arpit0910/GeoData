<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region_id',
        'wikiDataId',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
