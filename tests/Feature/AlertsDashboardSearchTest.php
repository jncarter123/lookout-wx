<?php

use App\Livewire\AlertsDashboard;
use App\Models\NwsAlert;
use Livewire\Livewire;

function searchIds(string $term): array
{
    return Livewire::test(AlertsDashboard::class)
        ->set('search', $term)
        ->viewData('alerts')
        ->pluck('id')
        ->sort()
        ->values()
        ->all();
}

function recentAlert(array $attributes = []): NwsAlert
{
    return NwsAlert::factory()->create(array_merge([
        'event' => 'Gale Warning',
        'headline' => 'Gale Warning issued',
        'nws_updated_at' => now()->subMinutes(5),
        'raw' => ['properties' => ['areaDesc' => 'Coastal waters']],
    ], $attributes));
}

test('search matches event, headline and area description, ignoring case', function () {
    $tornado = recentAlert(['event' => 'Tornado Warning']);
    $flood = recentAlert(['headline' => 'Flash Flood Warning issued for creeks']);
    $harris = recentAlert(['raw' => ['properties' => ['areaDesc' => 'Harris, TX; Fort Bend, TX']]]);
    recentAlert();

    expect(searchIds('tornado'))->toBe([$tornado->id])
        ->and(searchIds('FLASH FLOOD'))->toBe([$flood->id])
        ->and(searchIds('fort bend'))->toBe([$harris->id]);
});

test('search matches county UGC and zone ID', function () {
    $county = recentAlert();
    $county->counties()->create(['county_ugc' => 'TXC201']);
    $zone = recentAlert();
    $zone->zones()->create(['zone_id' => 'GMZ330', 'zone_kind' => 'forecast']);

    expect(searchIds('txc201'))->toBe([$county->id])
        ->and(searchIds('GMZ330'))->toBe([$zone->id]);
});

test('LIKE wildcards in the search are matched literally', function () {
    $percent = recentAlert(['headline' => 'Rain 100% likely']);
    recentAlert(['headline' => 'Rain 100 likely']);

    expect(searchIds('100%'))->toBe([$percent->id])
        ->and(searchIds('_'))->toBe([])
        ->and(searchIds('!'))->toBe([]);
});

test('search stays within the selected time window', function () {
    recentAlert(['event' => 'Tornado Warning', 'nws_updated_at' => now()->subHours(2)]);
    $recent = recentAlert(['event' => 'Tornado Warning']);

    expect(searchIds('tornado'))->toBe([$recent->id]);
});

test('a blank search shows every alert in the window, and a miss says so', function () {
    $alerts = collect([recentAlert(), recentAlert()])->pluck('id')->sort()->values()->all();

    expect(searchIds('   '))->toBe($alerts);

    Livewire::test(AlertsDashboard::class)
        ->set('search', 'nothing like this')
        ->assertSee('No alerts in this time window match “nothing like this”.');
});

test('changing the search returns to the first page', function () {
    NwsAlert::factory()->count(30)->create(['nws_updated_at' => now()->subMinutes(5)]);

    Livewire::test(AlertsDashboard::class)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->set('search', 'warning')
        ->assertSet('paginators.page', 1);
});
