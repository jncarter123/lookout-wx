<?php

namespace App\Jobs;

use App\Events\NwsAlertsChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Trailing-edge debounce for NwsAlertsChanged: the first change in a burst schedules this job,
 * later changes in the window ride along, and one ping goes out when the window closes.
 */
class BroadcastNwsAlertsChanged implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const int DEBOUNCE_SECONDS = 10;

    private const string PENDING_KEY = 'nws:alerts:broadcast-pending';

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('polling');
    }

    /**
     * Call after alert writes have committed.
     */
    public static function debounce(): void
    {
        // TTL is only a safety net so a lost job can't suppress pings forever; handle() clears it.
        if (Cache::add(self::PENDING_KEY, true, self::DEBOUNCE_SECONDS * 6)) {
            static::dispatch()->delay(self::DEBOUNCE_SECONDS);
        }
    }

    public function handle(): void
    {
        // Clear before broadcasting: a change landing after this schedules its own ping, and one
        // landing before it is already committed, so clients refreshing on this ping will see it.
        Cache::forget(self::PENDING_KEY);

        event(new NwsAlertsChanged);
    }
}
