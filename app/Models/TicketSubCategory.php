<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'status'];

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sub_category_id');
    }
}
