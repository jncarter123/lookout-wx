<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Payload-free "alerts changed, refetch" ping for the admin dashboards.
 *
 * One channel only: broadcast providers bill per channel per message, and fanning out to a
 * channel per county/zone multiplied every alert update ~100x. Downstream systems sync through
 * GET /api/alerts/changes instead. Dispatched debounced via BroadcastNwsAlertsChanged.
 */
class NwsAlertsChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function broadcastOn(): array
    {
        return [new Channel('nws.alerts')];
    }

    public function broadcastAs(): string
    {
        return 'NwsAlertsChanged';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
