<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast when alerts drop out of the NWS active feed (cancelled, superseded, or expired early).
 */
class NwsAlertsRemoved implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        /** @var string[] */
        public array $alertIds,
        /** @var string[] */
        public array $countyUgcs,
        /** @var string[] */
        public array $zoneIds,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('nws.alerts'),
        ];

        foreach ($this->countyUgcs as $ugc) {
            $channels[] = new Channel("nws.alerts.county.$ugc");
        }

        foreach ($this->zoneIds as $zoneId) {
            $channels[] = new Channel("nws.alerts.zone.$zoneId");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'NwsAlertsRemoved';
    }

    public function broadcastWith(): array
    {
        return [
            'alertIds' => $this->alertIds,
            'counties' => $this->countyUgcs,
            'zoneIds' => $this->zoneIds,
        ];
    }
}
