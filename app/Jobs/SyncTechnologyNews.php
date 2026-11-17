<?php

namespace App\Jobs;

use App\Services\TechnologyNews\Synchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class SyncTechnologyNews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $sourceId;
    public $tries = 1;
    public $timeout = 1800;
    public function __construct(int $sourceId) { $this->sourceId = $sourceId; }
    public function handle(Synchronizer $sync): void
    {
        $sync->run($this->sourceId, true);
        \Illuminate\Support\Facades\Artisan::call('news:rewrite', ['--source' => $this->sourceId]);
    }
}
