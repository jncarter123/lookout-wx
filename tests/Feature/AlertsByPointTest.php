<?php

use App\Models\NwsAlert;
use App\Models\User;
use App\Services\NwsGeoService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Cache::store('redis')->flush();
    Sanctum::actingAs(User::factory()->create());
});

function fakePointMetadata(?string $county, ?string $zone): void
{
    $geo = Mockery::mock(NwsGeoService::class)->makePartial();
    $geo->shouldReceive('getPointsMetadata')->andReturn(['properties' => [
        'county' => $county ? "https://api.weather.gov/zones/county/{$county}" : null,
        'forecastZone' => $zone ? "https://api.weather.gov/zones/forecast/{$zone}" : null,
    ]]);

    app()->instance(NwsGeoService::class, $geo);
}

test('offshore point with no county returns alerts for its marine zone', function () {
    fakePointMetadata(null, 'GMZ557');

    $marine = NwsAlert::factory()->create();
    $marine->zones()->create(['zone_id' => 'GMZ557', 'zone_kind' => 'forecast']);

    $elsewhere = NwsAlert::factory()->create();
    $elsewhere->zones()->create(['zone_id' => 'GMZ330', 'zone_kind' => 'forecast']);

    $this->getJson('/api/alerts/points?lat=29.8&lon=-88.5')
        ->assertOk()
        ->assertJsonPath('county', null)
        ->assertJsonPath('zone', 'GMZ557')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('alerts.0.id', $marine->id);
});

test('land point returns alerts matching either its county or its zone', function () {
    fakePointMetadata('TXC121', 'TXZ103');

    $byCounty = NwsAlert::factory()->create();
    $byCounty->counties()->create(['county_ugc' => 'TXC121']);

    $byZone = NwsAlert::factory()->create();
    $byZone->zones()->create(['zone_id' => 'TXZ103', 'zone_kind' => 'forecast']);

    $removed = NwsAlert::factory()->create(['removed_from_feed_at' => now()]);
    $removed->counties()->create(['county_ugc' => 'TXC121']);

    $ids = collect($this->getJson('/api/alerts/points?lat=33.2&lon=-97.1')
        ->assertOk()
        ->assertJsonPath('county', 'TXC121')
        ->assertJsonPath('count', 2)
        ->json('alerts'))->pluck('id')->sort()->values()->all();

    expect($ids)->toBe(collect([$byCounty->id, $byZone->id])->sort()->values()->all());
});

test('point that resolves to nothing returns 422', function () {
    fakePointMetadata(null, null);

    $this->getJson('/api/alerts/points?lat=0&lon=0')->assertStatus(422);
});
