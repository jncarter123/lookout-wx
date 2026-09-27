<?php

use App\Jobs\PruneAlertChanges;
use App\Models\NwsAlert;
use App\Models\NwsAlertChange;
use App\Models\User;
use App\Services\AlertChangesFeed;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

function settle(): void
{
    test()->travel(AlertChangesFeed::SETTLE_SECONDS + 1)->seconds();
}

test('snapshot returns active alerts with areas and a cursor', function () {
    $active = NwsAlert::factory()->create(['raw' => ['properties' => ['areaDesc' => 'Harris, TX', 'instruction' => 'Seek shelter']]]);
    $active->counties()->create(['county_ugc' => 'TXC201']);
    $active->zones()->create(['zone_id' => 'TXZ213', 'zone_kind' => 'forecast']);
    NwsAlert::factory()->create(['removed_from_feed_at' => now()]);
    NwsAlert::factory()->create(['expires' => now()->subMinute()]);

    NwsAlertChange::record([$active->id], NwsAlertChange::UPSERTED);
    settle();
    $cursor = NwsAlertChange::max('id');

    $this->getJson('/api/alerts/changes')
        ->assertOk()
        ->assertJsonPath('cursor', $cursor)
        ->assertJsonPath('hasMore', false)
        ->assertJsonCount(1, 'changes')
        ->assertJsonPath('changes.0.type', 'upserted')
        ->assertJsonPath('changes.0.alert.id', $active->id)
        ->assertJsonPath('changes.0.alert.active', true)
        ->assertJsonPath('changes.0.alert.counties', ['TXC201'])
        ->assertJsonPath('changes.0.alert.zones', ['TXZ213'])
        ->assertJsonPath('changes.0.alert.areaDesc', 'Harris, TX')
        ->assertJsonPath('changes.0.alert.instruction', 'Seek shelter');
});

test('since returns changes after the cursor collapsed to the latest per alert', function () {
    $a = NwsAlert::factory()->create();
    $b = NwsAlert::factory()->create(['removed_from_feed_at' => now()]);

    NwsAlertChange::record([$a->id], NwsAlertChange::UPSERTED);
    $cursor = NwsAlertChange::max('id');
    NwsAlertChange::record([$b->id], NwsAlertChange::UPSERTED);
    NwsAlertChange::record([$a->id], NwsAlertChange::UPSERTED);
    NwsAlertChange::record([$b->id], NwsAlertChange::REMOVED);
    settle();

    $response = $this->getJson("/api/alerts/changes?since={$cursor}")->assertOk();

    expect($response->json('cursor'))->toBe(NwsAlertChange::max('id'))
        ->and($response->json('hasMore'))->toBeFalse()
        ->and(collect($response->json('changes'))->map(fn ($c) => [$c['alertId'], $c['type'], $c['alert']['active']])->all())
        ->toBe([[$a->id, 'upserted', true], [$b->id, 'removed', false]]);
});

test('unsettled changes are held back', function () {
    $a = NwsAlert::factory()->create();
    NwsAlertChange::record([$a->id], NwsAlertChange::UPSERTED);

    $this->getJson('/api/alerts/changes?since=0')
        ->assertOk()
        ->assertJsonPath('cursor', 0)
        ->assertJsonPath('changes', []);

    settle();

    $this->getJson('/api/alerts/changes?since=0')->assertJsonCount(1, 'changes');
});

test('pages with hasMore when more changes than the limit', function () {
    $alerts = NwsAlert::factory()->count(3)->create();
    NwsAlertChange::record($alerts->pluck('id')->all(), NwsAlertChange::UPSERTED);
    settle();

    $first = $this->getJson('/api/alerts/changes?since=0&limit=2')
        ->assertJsonPath('hasMore', true)
        ->assertJsonCount(2, 'changes');

    $this->getJson('/api/alerts/changes?since='.$first->json('cursor').'&limit=2')
        ->assertJsonPath('hasMore', false)
        ->assertJsonCount(1, 'changes')
        ->assertJsonPath('changes.0.alertId', $alerts[2]->id);
});

test('a change for a pruned alert is reported as removed without alert data', function () {
    NwsAlertChange::record(['https://api.weather.gov/alerts/gone'], NwsAlertChange::UPSERTED);
    settle();

    $this->getJson('/api/alerts/changes?since=0')
        ->assertJsonPath('changes.0.type', 'removed')
        ->assertJsonPath('changes.0.alertId', 'https://api.weather.gov/alerts/gone')
        ->assertJsonPath('changes.0.alert', null);
});

test('a cursor pruned out of the history returns 410', function () {
    $a = NwsAlert::factory()->create();
    NwsAlertChange::record([$a->id], NwsAlertChange::UPSERTED);
    $old = NwsAlertChange::max('id');

    $this->travel(PruneAlertChanges::RETENTION_HOURS + 1)->hours();
    NwsAlertChange::record([$a->id], NwsAlertChange::UPSERTED);
    settle();

    $this->getJson("/api/alerts/changes?since={$old}")->assertOk();

    (new PruneAlertChanges)->handle();

    expect(NwsAlertChange::count())->toBe(1);
    $this->getJson("/api/alerts/changes?since={$old}")->assertStatus(410);
    $this->getJson('/api/alerts/changes?since='.NwsAlertChange::max('id'))->assertOk();
});

test('validates since and limit', function () {
    $this->getJson('/api/alerts/changes?since=-1')->assertUnprocessable();
    $this->getJson('/api/alerts/changes?since=abc')->assertUnprocessable();
    $this->getJson('/api/alerts/changes?since=0&limit=0')->assertUnprocessable();
    $this->getJson('/api/alerts/changes?since=0&limit='.(AlertChangesFeed::MAX_LIMIT + 1))->assertUnprocessable();
});
