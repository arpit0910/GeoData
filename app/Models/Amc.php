<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amc extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'synced_at' => 'datetime', 'icon_synced_at' => 'datetime'];

    protected $appends = ['favicon_url', 'favicon_path', 'favicon_source_url', 'icon_url'];

    protected $hidden = ['logo_path', 'logo_source_url'];

    public function getFaviconPathAttribute(): ?string
    {
        return $this->logo_path;
    }

    public function getFaviconSourceUrlAttribute(): ?string
    {
        return $this->logo_source_url;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->favicon_url;
    }

    public function funds()
    {
        return $this->hasMany(MfMaster::class, 'amc_name', 'name');
    }

    public function getWebsiteUrlAttribute(): ?string
    {
        $url = trim((string) $this->website);
        if ($url !== '' && ! preg_match('~^[a-z][a-z0-9+.-]*:~i', $url)) {
            $url = 'https://'.$url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url : null;
    }
}
