<?php

namespace App\Jobs;

use App\Services\NwsAlertsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollNwsAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Do not retry this job if it fails.
     */
    public int $tries = 1;

    public function __construct(
        public readonly ?string $area = null,
    ) {
        $this->onQueue('polling');
    }

    public function handle(NwsAlertsService $service): void
    {
        $service->pollOnce($this->area);
    }
}
