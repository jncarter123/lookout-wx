<?php

namespace App\Services;

use App\Exceptions\StaleAlertCursorException;
use App\Models\NwsAlert;
use App\Models\NwsAlertChange;

/**
 * Cursor-based feed of alert changes for downstream consumers (e.g. sv-cad).
 *
 * Consumers start with a snapshot (no cursor), then poll with the returned cursor.
 * Each change carries the alert's current state, so replaying a change is idempotent.
 */
class AlertChangesFeed
{
    /**
     * Change rows younger than this are held back. Auto-increment ids are allocated at insert
     * but become visible at commit, so concurrent ingest workers can commit out of id order;
     * without the lag a consumer could advance its cursor past a row that commits a moment later.
     */
    public const int SETTLE_SECONDS = 5;

    public const int DEFAULT_LIMIT = 500;

    public const int MAX_LIMIT = 1000;

    /**
     * Every currently active alert, plus the cursor to poll from next.
     */
    public function snapshot(): array
    {
        // Take the cursor before reading alerts: anything committed in between is replayed
        // on the next poll rather than skipped.
        $cursor = (int) $this->settledChanges()->max('id');

        $alerts = $this->alertsQuery()->active()->orderBy('id')->get();

        return [
            'cursor' => $cursor,
            'hasMore' => false,
            'changes' => $alerts->map(fn (NwsAlert $a) => $this->change(NwsAlertChange::UPSERTED, $a->id, $a))->all(),
        ];
    }

    /**
     * Changes after $since, collapsed to the latest change per alert.
     *
     * @throws StaleAlertCursorException when rows after $since may have been pruned.
     */
    public function since(int $since, int $limit = self::DEFAULT_LIMIT): array
    {
        $this->assertCursorRetained($since);

        $rows = $this->settledChanges()
            ->where('id', '>', $since)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'alert_id', 'type']);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        if ($rows->isEmpty()) {
            return ['cursor' => $since, 'hasMore' => false, 'changes' => []];
        }

        $latest = $rows->keyBy('alert_id')->sortBy('id');
        $alerts = $this->alertsQuery()->whereIn('id', $latest->keys())->get()->keyBy('id');

        return [
            'cursor' => $rows->last()->id,
            'hasMore' => $hasMore,
            'changes' => $latest
                ->map(fn (NwsAlertChange $c) => $this->change($c->type, $c->alert_id, $alerts->get($c->alert_id)))
                ->values()
                ->all(),
        ];
    }

    private function assertCursorRetained(int $since): void
    {
        if ($since === 0 || NwsAlertChange::query()->whereKey($since)->exists()) {
            return;
        }

        // The consumer's last-seen row is gone. If it sits below everything retained, rows it
        // never saw may have been pruned with it.
        $oldest = NwsAlertChange::query()->min('id');

        if ($oldest === null || $since < $oldest) {
            throw new StaleAlertCursorException;
        }
    }

    private function settledChanges()
    {
        return NwsAlertChange::query()->where('created_at', '<=', now()->subSeconds(self::SETTLE_SECONDS));
    }

    private function alertsQuery()
    {
        return NwsAlert::query()->with(['counties:alert_id,county_ugc', 'zones:alert_id,zone_id']);
    }

    private function change(string $type, string $alertId, ?NwsAlert $alert): array
    {
        return [
            // An alert pruned since the change was logged is reported as removed.
            'type' => $alert ? $type : NwsAlertChange::REMOVED,
            'alertId' => $alertId,
            'alert' => $alert ? $this->serialize($alert) : null,
        ];
    }

    private function serialize(NwsAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'active' => $alert->isActive(),
            'event' => $alert->event,
            'headline' => $alert->headline,
            'description' => data_get($alert->raw, 'properties.description'),
            'instruction' => data_get($alert->raw, 'properties.instruction'),
            'areaDesc' => data_get($alert->raw, 'properties.areaDesc'),
            'severity' => $alert->severity,
            'certainty' => $alert->certainty,
            'urgency' => $alert->urgency,
            'status' => $alert->status,
            'messageType' => $alert->message_type,
            'category' => $alert->category,
            'sent' => $alert->sent?->toIso8601String(),
            'effective' => $alert->effective?->toIso8601String(),
            'onset' => $alert->onset?->toIso8601String(),
            'expires' => $alert->expires?->toIso8601String(),
            'ends' => $alert->ends?->toIso8601String(),
            'updated' => $alert->nws_updated_at?->toIso8601String(),
            'removedFromFeedAt' => $alert->removed_from_feed_at?->toIso8601String(),
            'counties' => $alert->counties->pluck('county_ugc')->values()->all(),
            'zones' => $alert->zones->pluck('zone_id')->values()->all(),
        ];
    }
}
