<?php

namespace App\Services;

use App\Exceptions\UnresolvablePointException;
use App\Models\NwsAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Stored alerts, answered in the NWS API's own shape (a GeoJSON FeatureCollection of the
 * alert features NWS published), for the public /api/nws endpoints.
 *
 * Each feature is the alert as NWS served it (`nws_alerts.raw`), so its properties,
 * geometry and `@id` links are exactly what api.weather.gov returned.
 *
 * One difference from NWS is deliberate: NWS matches a point against an alert's polygon as
 * well as its zones, while this matches the point's county and forecast zone. A polygon
 * warning that clips part of a county is therefore returned for the whole county — more
 * alerts than NWS would give, never fewer.
 */
class NwsCompatibleAlertsService
{
    public const int MAX_LIMIT = 500;

    private const string CACHE_PREFIX = 'nws-compatible:active:';

    /** The JSON-LD context NWS puts on its alert responses. */
    private const array CONTEXT = [
        'https://geojson.org/geojson-ld/geojson-context.jsonld',
        [
            '@version' => '1.1',
            'wx' => 'https://api.weather.gov/ontology#',
            '@vocab' => 'https://api.weather.gov/ontology#',
        ],
    ];

    public function __construct(private readonly NwsGeoService $geoService) {}

    /**
     * Active alerts matching the filters, as NWS's /alerts/active answers them. Every
     * filter is optional; list filters match any of their values.
     *
     * @param  array{
     *     point?: array{0: float, 1: float},
     *     area?: list<string>,
     *     zone?: list<string>,
     *     status?: list<string>,
     *     message_type?: list<string>,
     *     event?: list<string>,
     *     severity?: list<string>,
     *     urgency?: list<string>,
     *     certainty?: list<string>,
     *     limit?: int,
     * }  $filters  Enumerated values already normalised to NWS's casing (`Actual`, `Severe`).
     * @return array<string, mixed>
     *
     * @throws UnresolvablePointException
     */
    public function activeAlerts(array $filters): array
    {
        if (isset($filters['point'])) {
            [$latitude, $longitude] = $filters['point'];

            $filters['point_county'] = $this->geoService->getCountyUgc($latitude, $longitude) ?: null;
            $filters['point_zone'] = $this->geoService->getForecastZoneId($latitude, $longitude) ?: null;

            if ($filters['point_county'] === null && $filters['point_zone'] === null) {
                throw new UnresolvablePointException($latitude, $longitude);
            }
        }

        $cacheKey = self::CACHE_PREFIX.sha1((string) json_encode($filters));

        return Cache::store('redis')->remember(
            $cacheKey,
            (int) config('wxalerts.nws_compatible.cache_seconds'),
            fn () => $this->collection($filters),
        );
    }

    /**
     * One alert as NWS's /alerts/{id} answers it, or null when Lookout does not hold it.
     *
     * @param  string  $id  The alert's id as NWS gives it (`urn:oid:…`), or its full URL.
     * @return array<string, mixed>|null
     */
    public function alert(string $id): ?array
    {
        $key = str_starts_with($id, 'https://') ? $id : 'https://api.weather.gov/alerts/'.$id;

        $alert = NwsAlert::query()->find($key);

        return $alert === null ? null : ['@context' => self::CONTEXT, ...$this->feature($alert)];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function collection(array $filters): array
    {
        $alerts = NwsAlert::query()
            ->active()
            ->when(array_key_exists('point_county', $filters), fn (Builder $q) => $this->whereInCountyOrZone(
                $q,
                array_filter([$filters['point_county']]),
                array_filter([$filters['point_zone']]),
            ))
            ->when(isset($filters['zone']), fn (Builder $q) => $this->whereInCountyOrZone(
                $q,
                $filters['zone'],
                $filters['zone'],
            ))
            ->when(isset($filters['area']), fn (Builder $q) => $this->whereInArea($q, $filters['area']))
            ->when(isset($filters['status']), fn (Builder $q) => $q->whereIn('status', $filters['status']))
            ->when(isset($filters['message_type']), fn (Builder $q) => $q->whereIn('message_type', $filters['message_type']))
            ->when(isset($filters['event']), fn (Builder $q) => $q->whereIn('event', $filters['event']))
            ->when(isset($filters['severity']), fn (Builder $q) => $q->whereIn('severity', $filters['severity']))
            ->when(isset($filters['urgency']), fn (Builder $q) => $q->whereIn('urgency', $filters['urgency']))
            ->when(isset($filters['certainty']), fn (Builder $q) => $q->whereIn('certainty', $filters['certainty']))
            ->orderByDesc('sent')
            ->when(isset($filters['limit']), fn (Builder $q) => $q->limit($filters['limit']))
            ->get();

        return [
            '@context' => self::CONTEXT,
            'type' => 'FeatureCollection',
            'features' => $alerts->map(fn (NwsAlert $alert) => $this->feature($alert))->all(),
            'title' => 'Current watches, warnings, and advisories',
            'updated' => now()->toIso8601String(),
        ];
    }

    /**
     * An NWS zone id is either a county (`TXC121`) or a forecast zone (`TXZ213`,
     * `GMZ557`); alerts record each kind in its own table.
     *
     * @param  list<string>  $counties
     * @param  list<string>  $zones
     */
    private function whereInCountyOrZone(Builder $query, array $counties, array $zones): void
    {
        $query->where(function (Builder $q) use ($counties, $zones) {
            if ($counties !== []) {
                $q->orWhereHas('counties', fn (Builder $c) => $c->whereIn('county_ugc', $counties));
            }

            if ($zones !== []) {
                $q->orWhereHas('zones', fn (Builder $z) => $z->whereIn('zone_id', $zones));
            }
        });
    }

    /**
     * A state (`TX`) or marine area (`GM`) is the first two letters of every county and
     * zone id within it.
     *
     * @param  list<string>  $areas
     */
    private function whereInArea(Builder $query, array $areas): void
    {
        $query->where(function (Builder $q) use ($areas) {
            $q->orWhereHas('counties', function (Builder $c) use ($areas) {
                $c->where(function (Builder $c) use ($areas) {
                    foreach ($areas as $area) {
                        $c->orWhere('county_ugc', 'like', $area.'%');
                    }
                });
            })->orWhereHas('zones', function (Builder $z) use ($areas) {
                $z->where(function (Builder $z) use ($areas) {
                    foreach ($areas as $area) {
                        $z->orWhere('zone_id', 'like', $area.'%');
                    }
                });
            });
        });
    }

    /**
     * The alert as NWS published it. Rows stored without the NWS body fall back to the
     * columns Lookout keeps, so every alert still answers in the same shape.
     *
     * @return array{id: string, type: string, geometry: mixed, properties: array<string, mixed>}
     */
    private function feature(NwsAlert $alert): array
    {
        $raw = is_array($alert->raw) ? $alert->raw : [];
        $properties = data_get($raw, 'properties');

        if (! is_array($properties) || $properties === []) {
            $properties = $this->propertiesFromColumns($alert);
        }

        return [
            'id' => $alert->id,
            'type' => 'Feature',
            'geometry' => $raw['geometry'] ?? null,
            'properties' => $properties,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesFromColumns(NwsAlert $alert): array
    {
        return [
            '@id' => $alert->id,
            '@type' => 'wx:Alert',
            'id' => str_replace('https://api.weather.gov/alerts/', '', $alert->id),
            'sent' => $alert->sent?->toIso8601String(),
            'effective' => $alert->effective?->toIso8601String(),
            'onset' => $alert->onset?->toIso8601String(),
            'expires' => $alert->expires?->toIso8601String(),
            'ends' => $alert->ends?->toIso8601String(),
            'status' => $alert->status,
            'messageType' => $alert->message_type,
            'category' => $alert->category,
            'severity' => $alert->severity,
            'certainty' => $alert->certainty,
            'urgency' => $alert->urgency,
            'event' => $alert->event,
            'headline' => $alert->headline,
        ];
    }
}
