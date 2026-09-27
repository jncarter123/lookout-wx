<?php

use App\Events\NwsAlertsChanged;
use App\Jobs\BroadcastNwsAlertsChanged;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cache::flush();
    Bus::fake([BroadcastNwsAlertsChanged::class]);
});

test('a burst of changes schedules a single delayed ping', function () {
    BroadcastNwsAlertsChanged::debounce();
    BroadcastNwsAlertsChanged::debounce();
    BroadcastNwsAlertsChanged::debounce();

    Bus::assertDispatchedTimes(BroadcastNwsAlertsChanged::class, 1);
    Bus::assertDispatched(BroadcastNwsAlertsChanged::class,
        fn ($job) => $job->delay === BroadcastNwsAlertsChanged::DEBOUNCE_SECONDS && $job->queue === 'polling');
});

test('changes after the ping goes out schedule another', function () {
    Event::fake([NwsAlertsChanged::class]);

    BroadcastNwsAlertsChanged::debounce();
    (new BroadcastNwsAlertsChanged)->handle();
    BroadcastNwsAlertsChanged::debounce();

    Bus::assertDispatchedTimes(BroadcastNwsAlertsChanged::class, 2);
    Event::assertDispatchedTimes(NwsAlertsChanged::class, 1);
});

test('the ping goes to the shared channel only, with no payload', function () {
    $event = new NwsAlertsChanged;

    expect(collect($event->broadcastOn())->map->name->all())->toBe(['nws.alerts'])
        ->and($event->broadcastAs())->toBe('NwsAlertsChanged')
        ->and($event->broadcastWith())->toBe([]);
});
