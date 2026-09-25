<?php

use App\Services\NwsAlertsApiService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::store('redis')->flush();
});

test('invalidateCounty clears the cache for a valid UGC', function () {
    $service = app(NwsAlertsApiService::class);

    Cache::store('redis')->put('api:alerts:county:TXC121', ['cached' => true], 60);
    expect(Cache::store('redis')->has('api:alerts:county:TXC121'))->toBeTrue();

    $service->invalidateCounty('TXC121');

    expect(Cache::store('redis')->has('api:alerts:county:TXC121'))->toBeFalse();
});

test('invalidateCounty does nothing for an invalid UGC', function () {
    $service = app(NwsAlertsApiService::class);

    // Should not throw; silently ignores
    $service->invalidateCounty('invalid');
    $service->invalidateCounty('');
    $service->invalidateCounty('TXZ001'); // zone, not county

    expect(true)->toBeTrue();
});

test('invalidateCounties clears caches for all provided UGCs', function () {
    $service = app(NwsAlertsApiService::class);

    Cache::store('redis')->put('api:alerts:county:TXC121', ['data' => 1], 60);
    Cache::store('redis')->put('api:alerts:county:FLC001', ['data' => 2], 60);
    Cache::store('redis')->put('api:alerts:county:CAC073', ['data' => 3], 60);

    $service->invalidateCounties(['TXC121', 'FLC001']);

    expect(Cache::store('redis')->has('api:alerts:county:TXC121'))->toBeFalse();
    expect(Cache::store('redis')->has('api:alerts:county:FLC001'))->toBeFalse();
    expect(Cache::store('redis')->has('api:alerts:county:CAC073'))->toBeTrue();
});

test('getAlertsByCounty throws for invalid UGC', function () {
    $service = app(NwsAlertsApiService::class);

    expect(fn () => $service->getAlertsByCounty('invalid'))
        ->toThrow(\InvalidArgumentException::class);
});
