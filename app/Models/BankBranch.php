<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'ifsc',
        'branch',
        'micr',
        'address',
        'contact',
        'city_id',
        'state_id',
        'imps',
        'rtgs',
        'neft',
        'upi',
        'swift',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
