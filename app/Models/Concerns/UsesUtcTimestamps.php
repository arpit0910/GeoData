<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

trait UsesUtcTimestamps
{
    public function freshTimestamp() { return Carbon::now('UTC'); }
    protected function asDateTime($value)
    {
        if (is_string($value)) { return Carbon::parse($value, 'UTC')->utc(); }
        return parent::asDateTime($value)->utc();
    }
}
