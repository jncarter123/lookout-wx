<?php

namespace App\Services;

use App\Http\Integrations\Nws\Nws;
use App\Http\Integrations\Nws\Requests\PointsMetadata;
use Illuminate\Support\Facades\Cache;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class NwsGeoService
{
    private Nws $nws;

    public function __construct(Nws $nws)
    {
        $this->nws = $nws;
    }

    /**
     * Retrieves metadata for a specified geographical point using latitude and longitude.
     *
     * This function normalizes latitude and longitude values, ensuring consistent
     * cache key generation to prevent cache misses caused by minor floating-point
     * differences. It attempts to retrieve the metadata from the cache, and if it
     * does not exist, fetches it from the NWS Geo API and stores it in the cache
     * for future use. The cache expiration is set to 30 days.
     *
     * If an error occurs during the cache lookup or API fetch process, it catches
     * the exception and returns an empty array, ensuring application stability.
     *
     * @param  float  $latitude  The latitude of the geographical point.
     * @param  float  $longitude  The longitude of the geographical point.
     * @return array The metadata of the geographical point or an empty array if an error occurs.
     */
    public function getPointsMetadata(float $latitude, float $longitude): array
    {
        try {
            // Normalize for cache key (avoid blowing cache with tiny float diffs)
            $lat = number_format((float) $latitude, 5, '.', '');
            $lon = number_format((float) $longitude, 5, '.', '');

            $cacheKey = "nws:county_ugc:{$lat},{$lon}";

            return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude) {
                return $this->doGetPointsMetadata($latitude, $longitude);
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetches metadata for a given geographical point based on latitude and longitude.
     *
     * This function sends a request to the NWS Geo API to retrieve point metadata.
     * In case of an error during the API request, it logs extensive information
     * including the exception, coordinates, request details, and response data
     * to facilitate debugging. The function throws a generic exception if the request fails.
     *
     * @param  float  $latitude  The latitude of the geographical point.
     * @param  float  $longitude  The longitude of the geographical point.
     * @return array The decoded JSON response containing the point metadata.
     *
     * @throws \Exception If the NWS Geo API request fails.
     */
    private function doGetPointsMetadata(float $latitude, float $longitude): array
    {
        try {
            $response = $this->nws->send(new PointsMetadata($latitude, $longitude));

            return $response->json();
        } catch (FatalRequestException|RequestException $e) {
            $context = [
                'exception' => $e,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            if ($e instanceof RequestException) {
                $res = $e->getResponse();
                $context['url'] = (string) $res->getPsrRequest()->getUri();
                $context['headers'] = $res->headers()->all();
                $context['body'] = $res->body() ?? 'unknown';
                $context['status'] = $res->status() ?? 'unknown';
            }

            \Log::error('NWS Geo API fetch failed: ', $context);
            throw new \Exception('NWS Geo API fetch failed.');
        }
    }

    public function getCountyUgc(float $latitude, float $longitude): string
    {
        $data = $this->getPointsMetadata($latitude, $longitude);

        // Example: /zones/county/TXC121 => TXC121
        return $this->lastPathSegment((string) data_get($data, 'properties.county', ''));
    }

    /**
     * Forecast zone for a point, e.g. TXZ213 on land or GMZ557 offshore.
     * Offshore points have no county, so this is the only way to match marine alerts.
     */
    public function getForecastZoneId(float $latitude, float $longitude): string
    {
        $data = $this->getPointsMetadata($latitude, $longitude);

        // Example: /zones/forecast/GMZ557 => GMZ557
        return $this->lastPathSegment((string) data_get($data, 'properties.forecastZone', ''));
    }

    private function lastPathSegment(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));

        return (string) end($segments);
    }
}
