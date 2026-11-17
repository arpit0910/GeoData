<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsSyncLog extends Model
{
    use \App\Models\Concerns\UsesUtcTimestamps;
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function source() { return $this->belongsTo(NewsSource::class); }
}
