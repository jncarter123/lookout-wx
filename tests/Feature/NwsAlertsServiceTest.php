<?php

use App\Events\NwsAlertsRemoved;
use App\Events\NwsAlertUpserted;
use App\Http\Integrations\Nws\Nws;
use App\Models\NwsAlert;
use App\Models\NwsAlertZone;
use App\Services\NwsAlertsApiService;
use App\Services\NwsAlertsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

const NWS_BASE = 'https://api.weather.gov';

beforeEach(function () {
    config(['wxalerts.nws.baseurl' => NWS_BASE]);
    Cache::store('redis')->flush(); // feed request rate limiter lives in redis
    Event::fake([NwsAlertUpserted::class, NwsAlertsRemoved::class]);
    Bus::fake();
});

function atomFeed(array $ids): string
{
    $entries = implode('', array_map(
        fn (string $id) => "<entry><id>{$id}</id><updated>2026-09-24T12:00:00+00:00</updated></entry>",
        $ids
    ));

    return '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom">' . $entries . '</feed>';
}

function alertJson(string $id, array $props = []): array
{
    return [
        'properties' => array_merge([
            '@id' => $id,
            'event' => 'Small Craft Advisory',
            'status' => 'Actual',
            'messageType' => 'Alert',
            'updated' => '2026-09-24T12:00:00+00:00',
            'expires' => now()->addHours(6)->toIso8601String(),
            'geocode' => ['UGC' => ['TXC121']],
            'affectedZones' => [NWS_BASE . '/zones/forecast/GMZ330'],
        ], $props),
    ];
}

function makeFeedService(MockClient $mockClient, ?NwsAlertsApiService $api = null): NwsAlertsService
{
    $nws = new Nws;
    $nws->withMockClient($mockClient);

    $api ??= tap(Mockery::mock(NwsAlertsApiService::class), fn ($m) => $m->shouldReceive('invalidateCounties'));

    return new NwsAlertsService($nws, $api);
}

// ─── Bug 1: alerts that leave the active feed ────────────────────────────────

test('pollOnce marks alerts missing from the feed as removed and broadcasts it', function () {
    $kept = NwsAlert::factory()->create();
    $gone = NwsAlert::factory()->create();
    $gone->counties()->create(['county_ugc' => 'FLC001']);
    $gone->zones()->create(['zone_id' => 'GMZ330', 'zone_kind' => 'forecast']);

    $api = Mockery::mock(NwsAlertsApiService::class);
    $api->shouldReceive('invalidateCounties')->once()->with(['FLC001']);

    makeFeedService(new MockClient([MockResponse::make(atomFeed([$kept->id]))]), $api)->pollOnce();

    expect($kept->fresh()->removed_from_feed_at)->toBeNull()
        ->and($gone->fresh()->removed_from_feed_at)->not->toBeNull();

    Event::assertDispatched(NwsAlertsRemoved::class, fn (NwsAlertsRemoved $e) => $e->alertIds === [$gone->id]
        && $e->countyUgcs === ['FLC001']
        && $e->zoneIds === ['GMZ330']);
});

test('pollOnce clears the removed mark when an alert reappears in the feed', function () {
    $alert = NwsAlert::factory()->create(['removed_from_feed_at' => now()->subMinutes(5)]);

    makeFeedService(new MockClient([MockResponse::make(atomFeed([$alert->id]))]))->pollOnce();

    expect($alert->fresh()->removed_from_feed_at)->toBeNull();
    Event::assertNotDispatched(NwsAlertsRemoved::class);
});

test('pollOnce does not reconcile against an area-filtered feed', function () {
    $other = NwsAlert::factory()->create();

    makeFeedService(new MockClient([MockResponse::make(atomFeed(['https://api.weather.gov/alerts/x']))]))->pollOnce('TX');

    expect($other->fresh()->removed_from_feed_at)->toBeNull();
});

test('pollOnce does not reconcile against an empty or unparseable feed', function () {
    $alert = NwsAlert::factory()->create();

    makeFeedService(new MockClient([MockResponse::make('not xml')]))->pollOnce();

    expect($alert->fresh()->removed_from_feed_at)->toBeNull();
});

test('inActiveFeed excludes removed alerts and cancellation notices', function () {
    $active = NwsAlert::factory()->create();
    NwsAlert::factory()->create(['removed_from_feed_at' => now()]);
    NwsAlert::factory()->create(['message_type' => 'Cancel']);
    $nullType = NwsAlert::factory()->create(['message_type' => null]);

    expect(NwsAlert::query()->inActiveFeed()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$active->id, $nullType->id])->sort()->values()->all());
});

// ─── Bug 2: validators stored only after successful processing ───────────────

test('failed processing is rolled back and the ETag is not reused', function () {
    $id = NWS_BASE . '/alerts/urn:oid:fail';
    $mockClient = new MockClient([
        MockResponse::make(alertJson($id), 200, ['ETag' => '"v1"']),
        MockResponse::make(alertJson($id), 200, ['ETag' => '"v1"']),
    ]);

    $service = makeFeedService($mockClient);

    // Fail partway through the database writes, once.
    $failed = false;
    NwsAlertZone::creating(function () use (&$failed) {
        if (! $failed) {
            $failed = true;
            throw new RuntimeException('db write failed');
        }
    });

    expect(fn () => $service->processAlertUrl($id, now()))->toThrow(RuntimeException::class)
        ->and(NwsAlert::find($id))->toBeNull();

    $service->processAlertUrl($id, now());

    expect($mockClient->getLastPendingRequest()->headers()->get('If-None-Match'))->toBeNull()
        ->and(NwsAlert::find($id)->zones()->pluck('zone_id')->all())->toBe(['GMZ330'])
        ->and(NwsAlert::find($id)->counties()->pluck('county_ugc')->all())->toBe(['TXC121']);
    Event::assertDispatchedTimes(NwsAlertUpserted::class, 1);
});

test('ETag is sent on the next request after successful processing', function () {
    $id = NWS_BASE . '/alerts/urn:oid:ok';
    $mockClient = new MockClient([
        MockResponse::make(alertJson($id), 200, ['ETag' => '"v1"']),
        MockResponse::make('', 304),
    ]);

    $service = makeFeedService($mockClient);

    $service->processAlertUrl($id, now());
    $service->processAlertUrl($id, now());

    expect($mockClient->getLastPendingRequest()->headers()->get('If-None-Match'))->toBe('"v1"');
});

test('feed ETag includes the area query in its cache key', function () {
    $mockClient = new MockClient([
        MockResponse::make(atomFeed(['https://api.weather.gov/alerts/a']), 200, ['ETag' => '"tx"']),
    ]);
    makeFeedService($mockClient)->pollOnce('TX');

    Cache::store('redis')->flush(); // reset rate limiter only; validators live in the default store

    $mockClient = new MockClient([MockResponse::make('', 304)]);
    makeFeedService($mockClient)->pollOnce('FL');

    expect($mockClient->getLastPendingRequest()->headers()->get('If-None-Match'))->toBeNull();

    Cache::store('redis')->flush();

    $mockClient = new MockClient([MockResponse::make('', 304)]);
    makeFeedService($mockClient)->pollOnce('TX');

    expect($mockClient->getLastPendingRequest()->headers()->get('If-None-Match'))->toBe('"tx"');
});

// ─── Bug 3: missing/invalid "updated" timestamp ──────────────────────────────

test('processAlertUrl falls back to the feed timestamp when updated is missing', function () {
    $id = NWS_BASE . '/alerts/urn:oid:no-updated';
    $feedUpdatedAt = Carbon::parse('2026-09-24T11:00:00Z');

    makeFeedService(new MockClient([
        MockResponse::make(alertJson($id, ['updated' => null])),
    ]))->processAlertUrl($id, $feedUpdatedAt);

    expect(NwsAlert::find($id)->nws_updated_at->equalTo($feedUpdatedAt))->toBeTrue();
});

test('processAlertUrl falls back to the feed timestamp when updated is unparseable', function () {
    $id = NWS_BASE . '/alerts/urn:oid:bad-updated';
    $feedUpdatedAt = Carbon::parse('2026-09-24T11:00:00Z');

    makeFeedService(new MockClient([
        MockResponse::make(alertJson($id, ['updated' => 'not a date'])),
    ]))->processAlertUrl($id, $feedUpdatedAt);

    expect(NwsAlert::find($id)->nws_updated_at->equalTo($feedUpdatedAt))->toBeTrue();
});
