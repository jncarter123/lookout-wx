<?php

use App\Http\Integrations\Nws\Nws;
use App\Services\NwsAlertsApiService;
use App\Services\NwsAlertsService;
use Carbon\Carbon;

function makeService(): NwsAlertsService
{
    return new NwsAlertsService(
        Mockery::mock(Nws::class),
        Mockery::mock(NwsAlertsApiService::class),
    );
}

function callPrivate(object $obj, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($obj, $method);
    $ref->setAccessible(true);

    return $ref->invokeArgs($obj, $args);
}

// ─── parseAtomEntries ────────────────────────────────────────────────────────

test('parseAtomEntries returns empty array for invalid XML', function () {
    $service = makeService();
    $prev = error_reporting(0);
    $result = callPrivate($service, 'parseAtomEntries', ['not xml at all <<<']);
    error_reporting($prev);
    expect($result)->toBe([]);
});

test('parseAtomEntries returns empty array for empty string', function () {
    $service = makeService();
    $prev = error_reporting(0);
    $result = callPrivate($service, 'parseAtomEntries', ['']);
    error_reporting($prev);
    expect($result)->toBe([]);
});

test('parseAtomEntries skips entries without id', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
      <entry>
        <updated>2026-01-01T00:00:00Z</updated>
      </entry>
    </feed>
    XML;

    $service = makeService();
    $result = callPrivate($service, 'parseAtomEntries', [$xml]);
    expect($result)->toBe([]);
});

test('parseAtomEntries extracts id and updatedAt from valid entries', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
      <entry>
        <id>https://api.weather.gov/alerts/urn:oid:2.49.0.1.840.0.abc123</id>
        <updated>2026-01-15T12:00:00+00:00</updated>
      </entry>
    </feed>
    XML;

    $service = makeService();
    $result = callPrivate($service, 'parseAtomEntries', [$xml]);

    expect($result)->toHaveCount(1);
    expect($result[0]['id'])->toBe('https://api.weather.gov/alerts/urn:oid:2.49.0.1.840.0.abc123');
    expect($result[0]['updatedAt'])->toBeInstanceOf(Carbon::class);
    expect($result[0]['updatedAt']->toDateString())->toBe('2026-01-15');
});

test('parseAtomEntries sets updatedAt to null when updated element is missing', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
      <entry>
        <id>https://api.weather.gov/alerts/urn:oid:2.49.0.1.840.0.abc123</id>
      </entry>
    </feed>
    XML;

    $service = makeService();
    $result = callPrivate($service, 'parseAtomEntries', [$xml]);

    expect($result)->toHaveCount(1);
    expect($result[0]['updatedAt'])->toBeNull();
});

test('parseAtomEntries handles multiple entries', function () {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
      <entry>
        <id>https://api.weather.gov/alerts/1</id>
        <updated>2026-01-15T10:00:00Z</updated>
      </entry>
      <entry>
        <id>https://api.weather.gov/alerts/2</id>
        <updated>2026-01-15T11:00:00Z</updated>
      </entry>
    </feed>
    XML;

    $service = makeService();
    $result = callPrivate($service, 'parseAtomEntries', [$xml]);

    expect($result)->toHaveCount(2);
    expect($result[0]['id'])->toBe('https://api.weather.gov/alerts/1');
    expect($result[1]['id'])->toBe('https://api.weather.gov/alerts/2');
});

// ─── extractCountyUgcs ───────────────────────────────────────────────────────

test('extractCountyUgcs returns valid UGC codes', function () {
    $props = ['geocode' => ['UGC' => ['TXC121', 'FLC001', 'CAC073']]];

    $service = makeService();
    $result = callPrivate($service, 'extractCountyUgcs', [$props]);

    expect($result)->toBe(['TXC121', 'FLC001', 'CAC073']);
});

test('extractCountyUgcs filters out invalid formats', function () {
    $props = ['geocode' => ['UGC' => ['TXC121', 'invalid', 'txc121', 'TXZ001', 'TXC12']]];

    $service = makeService();
    $result = callPrivate($service, 'extractCountyUgcs', [$props]);

    // Only county UGCs (XXC###) are valid; zone UGCs (XXZ###) are filtered out
    expect($result)->toBe(['TXC121']);
});

test('extractCountyUgcs deduplicates entries', function () {
    $props = ['geocode' => ['UGC' => ['TXC121', 'TXC121', 'FLC001']]];

    $service = makeService();
    $result = callPrivate($service, 'extractCountyUgcs', [$props]);

    expect($result)->toBe(['TXC121', 'FLC001']);
});

test('extractCountyUgcs returns empty array when geocode.UGC is missing', function () {
    $service = makeService();
    $result = callPrivate($service, 'extractCountyUgcs', [[]]);

    expect($result)->toBe([]);
});

// ─── extractForecastZoneIds ──────────────────────────────────────────────────

test('extractForecastZoneIds returns valid zone IDs from URLs', function () {
    $props = [
        'affectedZones' => [
            'https://api.weather.gov/zones/forecast/GMZ330',
            'https://api.weather.gov/zones/forecast/ANZ335',
        ],
    ];

    $service = makeService();
    $result = callPrivate($service, 'extractForecastZoneIds', [$props]);

    expect($result)->toBe(['GMZ330', 'ANZ335']);
});

test('extractForecastZoneIds filters non-forecast zone URLs', function () {
    $props = [
        'affectedZones' => [
            'https://api.weather.gov/zones/forecast/GMZ330',
            'https://api.weather.gov/zones/county/TXC121',
            'https://api.weather.gov/zones/fire/CAZ006',
            '',
        ],
    ];

    $service = makeService();
    $result = callPrivate($service, 'extractForecastZoneIds', [$props]);

    expect($result)->toBe(['GMZ330']);
});

test('extractForecastZoneIds deduplicates zone IDs', function () {
    $props = [
        'affectedZones' => [
            'https://api.weather.gov/zones/forecast/GMZ330',
            'https://api.weather.gov/zones/forecast/GMZ330',
        ],
    ];

    $service = makeService();
    $result = callPrivate($service, 'extractForecastZoneIds', [$props]);

    expect($result)->toBe(['GMZ330']);
});

test('extractForecastZoneIds returns empty array when affectedZones is missing', function () {
    $service = makeService();
    $result = callPrivate($service, 'extractForecastZoneIds', [[]]);

    expect($result)->toBe([]);
});
