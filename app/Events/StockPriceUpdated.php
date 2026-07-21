<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockPriceUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $symbol,
        public array $priceData
    ) {
    }

    /**
     * Broadcast to a public channel so dashboards can subscribe without
     * private channel authorization.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('market-data');
    }

    /**
     * Keep the frontend event name stable and explicit.
     */
    public function broadcastAs(): string
    {
        return 'StockPriceUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->symbol,
            'data' => $this->priceData,
        ];
    }
}
