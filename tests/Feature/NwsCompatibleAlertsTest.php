<?php

use App\Models\NwsAlert;
use App\Services\NwsGeoService;
use Illuminate\Support\Facades\Cache;

/*
 * The /api/nws endpoints stand in for api.weather.gov: a client switches by changing its
 * base URL and nothing else. So these pin what such a client relies on — no token, the
 * NWS response shape with the alert exactly as NWS published it, NWS-style errors — and
 * the one thing that must never happen: a place that could not be looked up reported as
 * having no alerts.
 */

beforeEach(function () {
    Cache::store('redis')->flush();
});

function nwsPoint(?string $county, ?string $zone): void
{
    $geo = Mockery::mock(NwsGeoService::class)->makePartial();
    $geo->shouldReceive('getPointsMetadata')->andReturn(['properties' => [
        'county' => $county ? "https://api.weather.gov/zones/county/{$county}" : null,
        'forecastZone' => $zone ? "https://api.weather.gov/zones/forecast/{$zone}" : null,
    ]]);

    app()->instance(NwsGeoService::class, $geo);
}

/**
 * An alert stored with the body NWS served for it.
 */
function publishedAlert(array $properties, array $attributes = []): NwsAlert
{
    $alert = NwsAlert::factory()->create($attributes);

    $alert->update(['raw' => [
        '@context' => ['https://geojson.org/geojson-ld/geojson-context.jsonld'],
        'id' => $alert->id,
        'type' => 'Feature',
        'geometry' => ['type' => 'Polygon', 'coordinates' => [[[-94.8, 29.3], [-94.7, 29.3], [-94.7, 29.4], [-94.8, 29.3]]]],
        'properties' => ['@id' => $alert->id, ...$properties],
    ]]);

    return $alert;
}

test('a point answers like NWS, without a token, with the alert as NWS published it', function () {
    nwsPoint('TXC167', 'GMZ335');

    $alert = publishedAlert([
        'event' => 'Small Craft Advisory',
        'headline' => 'Small Craft Advisory issued September 27',
        'severity' => 'Minor',
        'description' => 'Winds 20 to 25 knots.',
        'instruction' => 'Inexperienced mariners should avoid navigating.',
        'parameters' => ['VTEC' => ['/O.NEW.KHGX.SC.Y.0042.260927T1500Z-260928T0300Z/']],
    ]);
    $alert->zones()->create(['zone_id' => 'GMZ335', 'zone_kind' => 'forecast']);

    NwsAlert::factory()->create()->zones()->create(['zone_id' => 'GMZ330', 'zone_kind' => 'forecast']);

    $response = $this->get('/api/nws/alerts/active?point=29.3,-94.8')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/geo+json');

    $response->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.id', $alert->id)
        ->assertJsonPath('features.0.type', 'Feature')
        ->assertJsonPath('features.0.geometry.type', 'Polygon')
        // Everything NWS sent, not just the columns Lookout indexes.
        ->assertJsonPath('features.0.properties.description', 'Winds 20 to 25 knots.')
        ->assertJsonPath('features.0.properties.instruction', 'Inexperienced mariners should avoid navigating.')
        ->assertJsonPath('features.0.properties.parameters.VTEC.0', '/O.NEW.KHGX.SC.Y.0042.260927T1500Z-260928T0300Z/');

    // A collection's features carry no context of their own, as in NWS's.
    expect($response->json('features.0'))->not->toHaveKey('@context');
});

test('an alert stored without its NWS body still answers in the NWS shape', function () {
    nwsPoint('TXC121', null);

    $alert = NwsAlert::factory()->create(['event' => 'Flood Warning', 'severity' => 'Severe', 'raw' => []]);
    $alert->counties()->create(['county_ugc' => 'TXC121']);

    $this->get('/api/nws/alerts/active?point=33.2,-97.1')
        ->assertOk()
        ->assertJsonPath('features.0.properties.@id', $alert->id)
        ->assertJsonPath('features.0.properties.id', str_replace('https://api.weather.gov/alerts/', '', $alert->id))
        ->assertJsonPath('features.0.properties.event', 'Flood Warning')
        ->assertJsonPath('features.0.properties.severity', 'Severe')
        ->assertJsonPath('features.0.properties.messageType', 'Alert');
});

test('a zone filter matches both counties and forecast zones', function () {
    $county = NwsAlert::factory()->create();
    $county->counties()->create(['county_ugc' => 'TXC121']);

    $zone = NwsAlert::factory()->create();
    $zone->zones()->create(['zone_id' => 'GMZ557', 'zone_kind' => 'forecast']);

    NwsAlert::factory()->create()->zones()->create(['zone_id' => 'GMZ330', 'zone_kind' => 'forecast']);

    $ids = collect($this->get('/api/nws/alerts/active?zone=txc121,GMZ557')->assertOk()->json('features'))
        ->pluck('id')->sort()->values()->all();

    expect($ids)->toBe(collect([$county->id, $zone->id])->sort()->values()->all());
});

test('an area filter matches everything in that state or marine area', function () {
    $gulf = NwsAlert::factory()->create();
    $gulf->zones()->create(['zone_id' => 'GMZ557', 'zone_kind' => 'forecast']);

    NwsAlert::factory()->create()->counties()->create(['county_ugc' => 'TXC121']);

    $this->get('/api/nws/alerts/active?area=GM')
        ->assertOk()
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.id', $gulf->id);
});

test('enumerated filters take any case, as NWS does', function () {
    $severe = NwsAlert::factory()->create(['severity' => 'Severe', 'status' => 'Actual']);
    NwsAlert::factory()->create(['severity' => 'Minor', 'status' => 'Actual']);
    NwsAlert::factory()->create(['severity' => 'Severe', 'status' => 'Test']);

    $this->get('/api/nws/alerts/active?severity=severe,EXTREME&status=actual')
        ->assertOk()
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.id', $severe->id);
});

test('expired and removed alerts are not active', function () {
    NwsAlert::factory()->create(['expires' => now()->subMinute()]);
    NwsAlert::factory()->create(['removed_from_feed_at' => now()]);
    $live = NwsAlert::factory()->create();

    $this->get('/api/nws/alerts/active')
        ->assertOk()
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.id', $live->id);
});

test('a parameter this server cannot honour is refused, not ignored', function () {
    // Ignoring `region` would answer with every alert in the country.
    $this->get('/api/nws/alerts/active?region=GM')
        ->assertStatus(400)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 400)
        ->assertJsonPath('title', 'Bad Request')
        ->assertJsonStructure(['correlationId', 'title', 'type', 'status', 'detail', 'instance']);
});

test('malformed or conflicting parameters are refused as NWS problems', function (string $query) {
    $this->get('/api/nws/alerts/active?'.$query)
        ->assertStatus(400)
        ->assertHeader('Content-Type', 'application/problem+json');
})->with([
    'two locations' => 'point=29.3,-94.8&zone=GMZ335',
    'point not a pair' => 'point=29.3',
    'point out of range' => 'point=95,-94.8',
    'bad zone' => 'zone=GULF',
    'bad severity' => 'severity=catastrophic',
    'limit too large' => 'limit=501',
]);

test('a point that cannot be looked up is an error, never "no alerts"', function () {
    nwsPoint(null, null);

    $this->get('/api/nws/alerts/active?point=0,0')
        ->assertStatus(503)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After', '60');
});

test('a single alert is found by its NWS id, with its context', function () {
    $alert = publishedAlert(['event' => 'Gale Warning']);
    $urn = str_replace('https://api.weather.gov/alerts/', '', $alert->id);

    $this->get('/api/nws/alerts/'.$urn)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/geo+json')
        ->assertJsonPath('id', $alert->id)
        ->assertJsonPath('properties.event', 'Gale Warning')
        ->assertJsonStructure(['@context', 'id', 'type', 'geometry', 'properties']);

    $this->get('/api/nws/alerts/urn:oid:2.49.0.1.840.0.missing')
        ->assertStatus(404)
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('each client IP is rate limited, and told so as NWS would', function () {
    config(['wxalerts.nws_compatible.rate_limit' => 2]);

    $this->get('/api/nws/alerts/active')->assertOk();
    $this->get('/api/nws/alerts/active')->assertOk();

    $this->get('/api/nws/alerts/active')
        ->assertStatus(429)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After')
        ->assertJsonPath('status', 429);

    // Another client is not held up by the first.
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('/api/nws/alerts/active')
        ->assertOk();
});
