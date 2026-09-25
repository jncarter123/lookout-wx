<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\NwsGeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles geographic resolution endpoints backed by the NWS Points API.
 *
 * Provides two resolution operations for a lat/lon coordinate pair:
 *  - Full NWS point metadata (grid office, forecast zone, county URL, etc.)
 *  - County UGC code extracted from that metadata
 *
 * All heavy lifting (HTTP calls, caching) is delegated to NwsGeoService.
 */
class GeoController extends Controller
{
    private NwsGeoService $geoService;

    public function __construct(NwsGeoService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Resolve NWS point metadata for a given latitude/longitude.
     *
     * Validates that `lat` is in [-90, 90] and `lon` is in [-180, 180], then
     * proxies the request to NwsGeoService::getPointsMetadata(). Results are
     * cached by the service for 30 days.
     *
     * @param  Request  $request  Query parameters: lat (float), lon (float)
     * @return JsonResponse 200 with the full NWS properties payload,
     *                      or 404 if the service returned no data.
     */
    public function resolveMetadataFromPoint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $metadata = $this->geoService->getPointsMetadata($data['lat'], $data['lon']);

        if ($metadata) {
            return response()->json($metadata, 200);
        }

        return response()->json(null, 404);
    }

    /**
     * Resolve the county UGC code for a given latitude/longitude.
     *
     * Validates coordinates, normalises them to 5 decimal places (matching the
     * cache key used internally by NwsGeoService), and extracts the UGC segment
     * from the county URL returned by the NWS Points API.
     *
     * Example UGC: `TXC121` (derived from `/zones/county/TXC121`).
     *
     * @param  Request  $request  Query parameters: lat (float), lon (float)
     * @return JsonResponse 200 with `{ "ugc": "TXC121" }`,
     *                      or 404 with `{ "ugc": null }` if unresolvable.
     */
    public function resolveUgcFromPoint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Normalize for cache key (avoid blowing cache with tiny float diffs)
        $lat = number_format((float) $data['lat'], 5, '.', '');
        $lon = number_format((float) $data['lon'], 5, '.', '');

        $ugc = $this->geoService->getCountyUgc($lat, $lon);

        if ($ugc) {
            return response()->json(['ugc' => $ugc], 200);
        }

        return response()->json(['ugc' => null], 404);
    }
}
