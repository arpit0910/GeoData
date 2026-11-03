<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amc extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'synced_at' => 'datetime', 'icon_synced_at' => 'datetime'];

    protected $appends = ['icon_url'];

    public function getIconUrlAttribute(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    public function funds()
    {
        return $this->hasMany(MfMaster::class, 'amc_name', 'name');
    }
}
