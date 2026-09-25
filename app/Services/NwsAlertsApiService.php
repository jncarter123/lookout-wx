<?php

namespace App\Services;

use App\Models\NwsAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class NwsAlertsApiService
{
    private const CACHE_PREFIX = 'api:alerts:county:';
    private const POINT_CACHE_PREFIX = 'api:alerts:point:';
    private const CACHE_TTL_SECONDS = 15;

    public function getAlertsByCounty(string $ugc): array
    {
        $this->assertValidCountyUgc($ugc);

        $cacheKey = $this->countyCacheKey($ugc);

        return Cache::store('redis')->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($ugc) {
            $alerts = $this->activeAlerts(
                fn (Builder $q) => $q->whereHas('counties', fn ($q) => $q->where('county_ugc', $ugc))
            );

            return [
                'county' => $ugc,
                'count' => $alerts->count(),
                'alerts' => $alerts,
                'cachedAt' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Active alerts for a point, matched by its county and/or forecast zone.
     * Offshore points have only a (marine) zone; land points usually have both.
     */
    public function getAlertsForLocation(?string $countyUgc, ?string $zoneId): array
    {
        $countyUgc = $countyUgc !== null && preg_match('/^[A-Z]{2}C\d{3}$/', $countyUgc) ? $countyUgc : null;
        $zoneId = $zoneId !== null && preg_match('/^[A-Z]{2}Z\d{3}$/', $zoneId) ? $zoneId : null;

        if ($countyUgc === null && $zoneId === null) {
            throw new \InvalidArgumentException('A valid county UGC or forecast zone is required.');
        }

        // Not invalidated on ingest like county keys; the short TTL bounds staleness.
        $cacheKey = self::POINT_CACHE_PREFIX . ($countyUgc ?? '-') . ':' . ($zoneId ?? '-');

        return Cache::store('redis')->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($countyUgc, $zoneId) {
            $alerts = $this->activeAlerts(function (Builder $q) use ($countyUgc, $zoneId) {
                $q->where(function (Builder $q) use ($countyUgc, $zoneId) {
                    if ($countyUgc !== null) {
                        $q->orWhereHas('counties', fn ($q) => $q->where('county_ugc', $countyUgc));
                    }

                    if ($zoneId !== null) {
                        $q->orWhereHas('zones', fn ($q) => $q->where('zone_id', $zoneId));
                    }
                });
            });

            return [
                'county' => $countyUgc,
                'zone' => $zoneId,
                'count' => $alerts->count(),
                'alerts' => $alerts,
                'cachedAt' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @param  callable(Builder): mixed  $location
     */
    private function activeAlerts(callable $location): Collection
    {
        $now = Carbon::now();

        return NwsAlert::query()
            ->inActiveFeed()
            ->tap($location)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires')->orWhere('expires', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends')->orWhere('ends', '>', $now);
            })
            ->orderByDesc('sent')
            ->limit(200)
            ->get([
                'id','event','headline','severity','certainty','urgency','status','message_type',
                'sent','effective','onset','expires','ends','nws_updated_at'
            ]);
    }

    public function invalidateCounty(string $ugc): void
    {
        if (!preg_match('/^[A-Z]{2}C\d{3}$/', $ugc)) {
            return;
        }

        Cache::store('redis')->forget($this->countyCacheKey($ugc));
    }

    public function invalidateCounties(array $ugcs): void
    {
        foreach ($ugcs as $ugc) {
            if (is_string($ugc)) {
                $this->invalidateCounty($ugc);
            }
        }
    }

    private function assertValidCountyUgc(string $ugc): void
    {
        if (!preg_match('/^[A-Z]{2}C\d{3}$/', $ugc)) {
            throw new \InvalidArgumentException('Invalid county UGC. Example: TXC121');
        }
    }

    private function countyCacheKey(string $ugc): string
    {
        return self::CACHE_PREFIX . $ugc;
    }
}