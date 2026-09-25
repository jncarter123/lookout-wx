<?php

namespace App\Events;

use App\Models\NwsAlert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NwsAlertUpserted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $alertId,
        /** @var string[] */
        public array $countyUgcs,
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
        return 'NwsAlert';
    }

    public function broadcastWith(): array
    {
        $alert = NwsAlert::query()->find($this->alertId);

        return [
            'alertId' => $this->alertId,
            'counties' => $this->countyUgcs,
            'zoneIds' => $this->zoneIds,
            'event' => $alert?->event,
            'headline' => $alert?->headline,
            'severity' => $alert?->severity,
            'urgency' => $alert?->urgency,
            'certainty' => $alert?->certainty,
            'status' => $alert?->status,
            'messageType' => $alert?->message_type,
            'sent' => $alert?->sent?->toIso8601String(),
            'effective' => $alert?->effective?->toIso8601String(),
            'expires' => $alert?->expires?->toIso8601String(),
            'ends' => $alert?->ends?->toIso8601String(),
        ];
    }
}
