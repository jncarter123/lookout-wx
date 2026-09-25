<?php

namespace App\Jobs;

use App\Models\NwsAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PruneExpiredAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $cutoff = now()->subDay();

        NwsAlert::query()
            ->where('expires', '<', $cutoff)
            ->orWhere(function ($q) use ($cutoff) {
                $q->whereNull('expires')
                    ->where('ends', '<', $cutoff);
            })
            // Covers alerts with neither expires nor ends, which would otherwise never be pruned.
            ->orWhere('removed_from_feed_at', '<', $cutoff)
            ->delete();
    }
}
