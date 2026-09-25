<?php

namespace App\Services;

use App\Events\NwsAlertsRemoved;
use App\Events\NwsAlertUpserted;
use App\Http\Integrations\Nws\HttpValidatorCache;
use App\Http\Integrations\Nws\Nws;
use App\Http\Integrations\Nws\Requests\ActiveAlertsAtomFeed;
use App\Http\Integrations\Nws\Requests\AlertByUrl;
use App\Jobs\ProcessNwsAlertsBatch;
use App\Models\NwsAlert;
use App\Models\NwsAlertCounty;
use App\Models\NwsAlertZone;
use App\Traits\ParsesCarbon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;

class NwsAlertsService
{
    use ParsesCarbon;

    private const int PROCESS_ALERTS_CHUNK_SIZE = 10;

    private const ?string FEED_DEFAULT_AREA = null;

    public function __construct(
        private readonly Nws $nws,
        private readonly NwsAlertsApiService $api,
        private readonly HttpValidatorCache $validators = new HttpValidatorCache,
    ) {}

    /**
     * Poll Atom feed once and dispatch ProcessNwsAlertsBatch jobs for changed entries.
     */
    public function pollOnce(?string $area = self::FEED_DEFAULT_AREA): void
    {
        try {
            $response = $this->nws
                ->send(new ActiveAlertsAtomFeed($area));

            $requestedUrl = (string) $response->getPsrRequest()->getUri();

            if ($response->status() === 304) {
                return;
            }

            $entries = $this->parseAtomEntries($response->body());

            $ids = array_values(array_unique(array_map(
                static fn (array $e) => (string) ($e['id'] ?? ''),
                $entries
            )));
            $ids = array_values(array_filter($ids, static fn (string $id) => $id !== ''));

            // An empty result is far more likely a parse/transport problem than a genuinely
            // empty national feed, so don't store validators or reconcile against it.
            if ($ids === []) {
                return;
            }

            // Only the unfiltered feed is authoritative for "no longer active".
            if ($area === null) {
                $this->reconcileWithFeed($ids);
            }

            $existingUpdated = NwsAlert::query()
                ->whereIn('id', $ids)
                ->get(['id', 'nws_updated_at'])
                ->keyBy('id');

            $changed = [];

            foreach ($entries as $entry) {
                $id = (string) ($entry['id'] ?? '');
                if ($id === '') {
                    continue;
                }

                /** @var Carbon|null $feedUpdatedAt */
                $feedUpdatedAt = $entry['updatedAt'] ?? null;

                $existing = $existingUpdated->get($id);
                $existingUpdatedAt = $existing?->nws_updated_at;

                if (! $feedUpdatedAt) {
                    $changed[] = ['url' => $id, 'updatedAt' => null];

                    continue;
                }

                if (! $existingUpdatedAt || $feedUpdatedAt->gt($existingUpdatedAt)) {
                    $changed[] = ['url' => $id, 'updatedAt' => $feedUpdatedAt->toAtomString()];
                }
            }

            foreach (array_chunk($changed, self::PROCESS_ALERTS_CHUNK_SIZE) as $chunk) {
                ProcessNwsAlertsBatch::dispatch($chunk);
            }

            $this->validators->remember($response);
        } catch (FatalRequestException|RequestException|RateLimitReachedException $e) {
            \Log::error('NWS Alerts poll failed: ', [
                'exception' => $e,
                'url' => $requestedUrl ?? 'unknown',
            ]);

            return;
        }
    }

    /**
     * Fetch an alert JSON (geo+json), upsert DB, sync counties, broadcast event.
     */
    public function processAlertUrl(string $alertUrl, Carbon $feedUpdatedAt): void
    {
        try {
            $response = $this->nws
                ->send(new AlertByUrl($alertUrl));

            if ($response->status() === 304) {
                return;
            }

            $json = $response->json();
            $props = (array) data_get($json, 'properties', []);

            $alertId = (string) data_get($props, '@id', $alertUrl);
            if ($alertId === '') {
                $alertId = $alertUrl;
            }

            $nwsUpdatedAt = $this->tryParseCarbon((string) data_get($props, 'updated')) ?? $feedUpdatedAt;

            // Dedupe: if DB has same/newer nws_updated_at, skip.
            $existing = NwsAlert::query()->find($alertId);
            if ($existing && $existing->nws_updated_at && $existing->nws_updated_at->gte($nwsUpdatedAt)) {
                $this->validators->remember($response);

                return;
            }

            $countyUgcs = $this->extractCountyUgcs($props);
            $zoneIds = $this->extractForecastZoneIds($props);

            // All-or-nothing: a partial write would bump nws_updated_at and make
            // the dedupe check above skip every retry.
            $oldCountyUgcs = DB::transaction(function () use ($alertId, $props, $nwsUpdatedAt, $json, $countyUgcs, $zoneIds) {
                $this->upsertAlert($alertId, $props, $nwsUpdatedAt, $json);

                // Collect old county UGCs before replacing them
                $oldCountyUgcs = NwsAlertCounty::query()
                    ->where('alert_id', $alertId)
                    ->pluck('county_ugc')
                    ->all();

                // sync affected counties (bypass observer; invalidate caches in bulk below)
                $this->deleteCountiesByAlertId($alertId);
                $this->insertCountyAlerts($alertId, $countyUgcs);

                // sync affected zones (from properties.affectedZones)
                $this->deleteZonesByAlertId($alertId);
                $this->insertAlertZones($alertId, $zoneIds);

                return $oldCountyUgcs;
            });

            // Single batch cache invalidation for all old + new counties
            $this->api->invalidateCounties(array_unique(array_merge($oldCountyUgcs, $countyUgcs)));

            // send broadcast event
            event(new NwsAlertUpserted($alertId, $countyUgcs, $zoneIds));

            // Only now is this version safely stored; a 304 next time is correct.
            $this->validators->remember($response);
        } catch (FatalRequestException|RequestException $e) {
            $context = [
                'exception' => $e,
                'url' => $alertUrl,
            ];

            if ($e instanceof RequestException) {
                $res = $e->getResponse();
                $context['headers'] = $res->headers()->all();
                $context['body'] = $res->body() ?? 'unknown';
                $context['status'] = $res->status() ?? 'unknown';
            }

            \Log::error('NWS Alerts fetch failed: ', $context);
        } catch (\JsonException $e) {
            \Log::error('Failed to parse JSON response from NWS Alerts API: ', [
                'exception' => $e,
            ]);
        }
    }

    // -------------------------
    // Internals
    // -------------------------

    /**
     * Mark alerts that have dropped out of the active feed (cancelled, superseded, or
     * expired early) so they stop appearing as active, and clear the mark on any that
     * have reappeared.
     *
     * @param  string[]  $feedIds  Alert IDs present in the current unfiltered feed.
     */
    private function reconcileWithFeed(array $feedIds): void
    {
        NwsAlert::query()
            ->whereIn('id', $feedIds)
            ->whereNotNull('removed_from_feed_at')
            ->update(['removed_from_feed_at' => null]);

        $removed = NwsAlert::query()
            ->whereNull('removed_from_feed_at')
            ->whereNotIn('id', $feedIds)
            ->with(['counties:alert_id,county_ugc', 'zones:alert_id,zone_id'])
            ->get(['id']);

        if ($removed->isEmpty()) {
            return;
        }

        $removedIds = $removed->pluck('id')->all();

        NwsAlert::query()
            ->whereIn('id', $removedIds)
            ->update(['removed_from_feed_at' => now()]);

        $countyUgcs = $removed->pluck('counties')->flatten()->pluck('county_ugc')->unique()->values()->all();
        $zoneIds = $removed->pluck('zones')->flatten()->pluck('zone_id')->unique()->values()->all();

        $this->api->invalidateCounties($countyUgcs);

        event(new NwsAlertsRemoved($removedIds, $countyUgcs, $zoneIds));
    }

    private function deleteZonesByAlertId(string $alertId): void
    {
        NwsAlertZone::query()->where('alert_id', $alertId)->delete();
    }

    /**
     * Extract zone IDs like GMZ330 from properties.affectedZones URLs:
     *   https://api.weather.gov/zones/forecast/GMZ330
     */
    private function extractForecastZoneIds(array $props): array
    {
        $zones = (array) data_get($props, 'affectedZones', []);
        $out = [];

        foreach ($zones as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            if (! preg_match('~/zones/forecast/([A-Z]{3}\d{3})$~', $url, $m)) {
                continue;
            }

            $out[] = $m[1];
        }

        return array_values(array_unique($out));
    }

    private function insertAlertZones(string $alertId, array $zoneIds): void
    {
        foreach ($zoneIds as $zoneId) {
            NwsAlertZone::query()->create([
                'alert_id' => $alertId,
                'zone_id' => $zoneId,
                'zone_kind' => 'forecast',
            ]);
        }
    }

    /**
     * Insert or update an alert in the database using the given properties.
     *
     * @param  string  $alertId  The unique identifier of the alert.
     * @param  array  $props  The associative array of alert properties.
     * @param  Carbon  $nwsUpdatedAt  The timestamp of the alert's last update from the NWS.
     * @return NwsAlert The upserted alert instance.
     */
    private function upsertAlert(string $alertId, array $props, Carbon $nwsUpdatedAt, $json): NwsAlert
    {
        return NwsAlert::query()->updateOrCreate(
            ['id' => $alertId],
            [
                'event' => data_get($props, 'event'),
                'headline' => data_get($props, 'headline'),
                'severity' => data_get($props, 'severity'),
                'certainty' => data_get($props, 'certainty'),
                'urgency' => data_get($props, 'urgency'),
                'status' => data_get($props, 'status'),
                'message_type' => data_get($props, 'messageType'),
                'category' => is_array(data_get($props, 'category'))
                    ? implode(',', data_get($props, 'category'))
                    : data_get($props, 'category'),

                'sent' => $this->tryParseCarbon((string) data_get($props, 'sent')),
                'effective' => $this->tryParseCarbon((string) data_get($props, 'effective')),
                'onset' => $this->tryParseCarbon((string) data_get($props, 'onset')),
                'expires' => $this->tryParseCarbon((string) data_get($props, 'expires')),
                'ends' => $this->tryParseCarbon((string) data_get($props, 'ends')),

                'raw' => $json,
                'nws_updated_at' => $nwsUpdatedAt,
            ]
        );
    }

    /**
     * Inserts county alert records into the database for the given alert ID and an array of county UGCs.
     *
     * This method iterates over the provided county UGCs and creates a new record
     * in the `NwsAlertCounty` model for each UGC, associating it with the specified alert ID.
     *
     * @param  string  $alertId  The unique identifier for the alert to be associated with the county UGCs.
     * @param  array  $countyUgcs  An array of county UGC strings to be inserted into the database.
     */
    private function insertCountyAlerts(string $alertId, array $countyUgcs): void
    {
        // Bypass observer; cache invalidation is handled in bulk by the caller
        NwsAlertCounty::withoutEvents(function () use ($alertId, $countyUgcs) {
            foreach ($countyUgcs as $ugc) {
                NwsAlertCounty::query()->create([
                    'alert_id' => $alertId,
                    'county_ugc' => $ugc,
                ]);
            }
        });
    }

    private function deleteCountiesByAlertId(string $alertId): void
    {
        // Bypass observer; cache invalidation is handled in bulk by the caller
        NwsAlertCounty::withoutEvents(
            fn () => NwsAlertCounty::query()->where('alert_id', $alertId)->delete()
        );
    }

    /**
     * Parses an Atom XML string to extract and return a list of entries, each containing
     * an identifier and an optional updated timestamp.
     *
     * The method uses XPath to locate Atom entry elements, and for each valid entry, it extracts
     * the `id` and attempts to parse the `updated` timestamp into a Carbon instance. Entries with
     * missing or invalid `id` fields are excluded.
     *
     * @param  string  $xmlString  The Atom XML string to be parsed.
     * @return array An array of parsed entries, where each entry is an associative array containing:
     *               - 'id' (string): The unique identifier of the entry.
     *               - 'updatedAt' (\Carbon\Carbon|null): The parsed updated timestamp, or null if unavailable or invalid.
     */
    private function parseAtomEntries(string $xmlString): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $xml) {
            return [];
        }

        $xml->registerXPathNamespace('a', 'http://www.w3.org/2005/Atom');
        $entries = $xml->xpath('//a:entry') ?: [];

        $out = [];

        foreach ($entries as $entry) {
            $id = (string) ($entry->id ?? '');
            if ($id === '') {
                continue;
            }

            $updatedRaw = (string) ($entry->updated ?? '');
            $out[] = [
                'id' => $id,
                'updatedAt' => $updatedRaw !== '' ? $this->tryParseCarbon($updatedRaw) : null,
            ];
        }

        return $out;
    }

    /**
     * Extracts and returns a list of unique county-specific UGC (Universal Geographic Code) strings
     * from the provided properties array.
     *
     * The method filters the UGC values to ensure they are strings and match the
     * expected pattern of two uppercase letters followed by "C" and three digits (e.g., "XXC123").
     *
     * @param  array  $props  An associative array of properties, expected to include the 'geocode.UGC' key.
     * @return array A filtered and unique array of valid UGC strings.
     */
    private function extractCountyUgcs(array $props): array
    {
        $ugcs = (array) data_get($props, 'geocode.UGC', []);

        return array_values(array_unique(array_filter($ugcs, function ($v) {
            return is_string($v) && preg_match('/^[A-Z]{2}C\d{3}$/', $v);
        })));
    }
}
