<?php

namespace App\Jobs;

use App\Models\NwsAlertChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PruneAlertChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const int RETENTION_HOURS = 48;

    public int $tries = 1;

    public function handle(): void
    {
        // Prune by id, not timestamp, so the retained rows are always a contiguous tail;
        // AlertChangesFeed relies on that to detect cursors that fell off the end.
        $through = NwsAlertChange::query()
            ->where('created_at', '<', now()->subHours(self::RETENTION_HOURS))
            ->max('id');

        if ($through !== null) {
            NwsAlertChange::query()->where('id', '<=', $through)->delete();
        }
    }
}
