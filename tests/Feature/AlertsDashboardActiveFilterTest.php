<?php

use App\Livewire\AlertsDashboard;
use App\Models\NwsAlert;
use Livewire\Livewire;

function dashboardIds(array $set): array
{
    $component = Livewire::test(AlertsDashboard::class);

    foreach ($set as $property => $value) {
        $component->set($property, $value);
    }

    return $component->viewData('alerts')->pluck('id')->sort()->values()->all();
}

test('active only shows every active alert, ignoring the time window', function () {
    $recent = ['nws_updated_at' => now()->subMinutes(5)];

    $active = NwsAlert::factory()->create($recent);
    $expired = NwsAlert::factory()->create([...$recent, 'expires' => now()->subMinute()]);
    $ended = NwsAlert::factory()->create([...$recent, 'expires' => null, 'ends' => now()->subMinute()]);
    $removed = NwsAlert::factory()->create([...$recent, 'removed_from_feed_at' => now()]);
    $cancel = NwsAlert::factory()->create([...$recent, 'message_type' => 'Cancel']);
    $older = NwsAlert::factory()->create(['nws_updated_at' => now()->subDays(2)]); // active, outside any window
    NwsAlert::factory()->create(['nws_updated_at' => now()->subDays(2), 'expires' => now()->subMinute()]);

    expect(dashboardIds(['activeOnly' => true]))->toBe(collect([$active, $older])->pluck('id')->sort()->values()->all())
        ->and(dashboardIds(['activeOnly' => false]))->toBe(
            collect([$active, $expired, $ended, $removed, $cancel])->pluck('id')->sort()->values()->all()
        );
});

test('active only combines with search', function () {
    $recent = ['nws_updated_at' => now()->subMinutes(5)];

    $activeTornado = NwsAlert::factory()->create([...$recent, 'event' => 'Tornado Warning']);
    NwsAlert::factory()->create([...$recent, 'event' => 'Tornado Warning', 'expires' => now()->subMinute()]);
    NwsAlert::factory()->create([...$recent, 'event' => 'Gale Warning']);

    expect(dashboardIds(['activeOnly' => true, 'search' => 'tornado']))->toBe([$activeTornado->id]);

    Livewire::test(AlertsDashboard::class)
        ->set('activeOnly', true)
        ->set('search', 'nothing like this')
        ->assertSee('No active alerts match “nothing like this”.');
});

test('toggling active only returns to the first page and labels the results', function () {
    NwsAlert::factory()->count(30)->create(['nws_updated_at' => now()->subMinutes(5), 'expires' => now()->subMinute()]);

    Livewire::test(AlertsDashboard::class)
        ->call('gotoPage', 2)
        ->set('activeOnly', true)
        ->assertSet('paginators.page', 1)
        ->assertSee('Showing all active alerts, whenever they were updated')
        ->assertSee('No alerts are active right now.');
});
