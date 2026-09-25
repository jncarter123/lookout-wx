<?php

use App\Jobs\PruneExpiredAlerts;
use App\Models\NwsAlert;

test('prunes old expired, ended, and removed-from-feed alerts only', function () {
    $expired = NwsAlert::factory()->create(['expires' => now()->subDays(2)]);
    $ended = NwsAlert::factory()->create(['expires' => null, 'ends' => now()->subDays(2)]);
    $removedNoTimes = NwsAlert::factory()->create([
        'expires' => null, 'ends' => null, 'removed_from_feed_at' => now()->subDays(2),
    ]);
    $recentlyRemoved = NwsAlert::factory()->create(['removed_from_feed_at' => now()->subHour()]);
    $active = NwsAlert::factory()->create(['expires' => now()->addHour()]);

    (new PruneExpiredAlerts)->handle();

    expect(NwsAlert::pluck('id')->sort()->values()->all())
        ->toBe(collect([$recentlyRemoved->id, $active->id])->sort()->values()->all());
});
