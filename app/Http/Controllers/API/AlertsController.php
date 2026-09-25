<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\NwsAlertsApiService;
use App\Services\NwsGeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public function __construct(
        private readonly NwsAlertsApiService $alertService,
        private readonly NwsGeoService $geoService
    )
    {}

    public function byCounty(string $ugc): JsonResponse
    {
        try {
            $payload = $this->alertService->getAlertsByCounty($ugc);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    /**
     * Handle the retrieval of weather alerts for a specific geographic location based on latitude and longitude.
     *
     * Validates the request parameters to ensure they are within the accepted ranges for latitude (-90 to 90)
     * and longitude (-180 to 180). If validation passes, it resolves the point's county and forecast zone and
     * fetches alerts for either. Offshore points have no county, so marine alerts match on the zone (e.g. GMZ557).
     * In case of an error during processing, an error response is returned.
     *
     * @param Request $request The HTTP request instance containing latitude and longitude.
     *
     * @return JsonResponse A response containing either the fetched weather alerts or an error message if processing fails.
     *
     * @throws \Exception If there is an issue processing the request.
     */
    public function byPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $county = $this->geoService->getCountyUgc($validated['lat'], $validated['lon']);
            $zone = $this->geoService->getForecastZoneId($validated['lat'], $validated['lon']);
            $alerts = $this->alertService->getAlertsForLocation($county ?: null, $zone ?: null);

            return response()->json($alerts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'There was an issue retrieving alerts for the provided coordinates'], 422
            );
        }
    }
}