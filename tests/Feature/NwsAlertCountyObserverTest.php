<?php

use App\Models\NwsAlert;
use App\Models\NwsAlertCounty;
use App\Services\NwsAlertsApiService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::store('redis')->flush();
});

test('observer invalidates cache when county is created', function () {
    $alert = NwsAlert::factory()->create();

    Cache::store('redis')->put('api:alerts:county:TXC121', ['cached' => true], 60);

    NwsAlertCounty::create([
        'alert_id' => $alert->id,
        'county_ugc' => 'TXC121',
    ]);

    expect(Cache::store('redis')->has('api:alerts:county:TXC121'))->toBeFalse();
});

test('observer invalidates cache when county is deleted', function () {
    $alert = NwsAlert::factory()->create();

    $county = NwsAlertCounty::withoutEvents(fn () => NwsAlertCounty::create([
        'alert_id' => $alert->id,
        'county_ugc' => 'FLC001',
    ]));

    Cache::store('redis')->put('api:alerts:county:FLC001', ['cached' => true], 60);

    $county->delete();

    expect(Cache::store('redis')->has('api:alerts:county:FLC001'))->toBeFalse();
});
