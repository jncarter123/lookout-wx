<?php

namespace App\Jobs;

use App\Services\NwsAlertsService;
use App\Traits\ParsesCarbon;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNwsAlertsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ParsesCarbon;

    public int $tries = 1;

    /**
     * @param array<int, array{url: string, updatedAt: string|null}> $alerts
     */
    public function __construct(
        public readonly array $alerts,
    ) {
        $this->onQueue('processing');
    }

    public function handle(NwsAlertsService $service): void
    {
        foreach ($this->alerts as $a) {
            $url = (string) ($a['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $feedUpdatedAt = $this->tryParseCarbon($a['updatedAt'] ?? null) ?? Carbon::now('UTC');

            $service->processAlertUrl(
                alertUrl: $url,
                feedUpdatedAt: $feedUpdatedAt,
            );
        }
    }

}
