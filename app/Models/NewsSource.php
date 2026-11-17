<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsSource extends Model
{
    use \App\Models\Concerns\UsesUtcTimestamps;
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean', 'allow_ai_rewrite' => 'boolean', 'allow_feed_content' => 'boolean', 'last_sync_at' => 'datetime', 'last_success_at' => 'datetime', 'retry_after_at' => 'datetime', 'permissions_verified_at' => 'datetime'];
    public function displayPermitted(): bool
    {
        return $this->is_active && $this->permissions_verified_at && $this->permission_reference && in_array($this->display_mode, ['headline', 'excerpt'], true);
    }
    public function rewritePermitted(): bool
    {
        return $this->displayPermitted() && (bool) $this->allow_ai_rewrite;
    }
}
