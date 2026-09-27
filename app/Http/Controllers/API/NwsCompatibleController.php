<?php

namespace App\Http\Controllers\API;

use App\Exceptions\UnresolvablePointException;
use App\Http\Controllers\Controller;
use App\Http\Responses\NwsProblem;
use App\Services\NwsCompatibleAlertsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The NWS API's alert endpoints, answered from Lookout's store: public and rate-limited,
 * like api.weather.gov itself, so a client pointed here instead of NWS needs no other
 * change. Only the parameters Lookout can honour are accepted; any other is refused
 * rather than ignored, because ignoring a filter would return more than was asked for.
 */
class NwsCompatibleController extends Controller
{
    private const array SUPPORTED_PARAMETERS = [
        'point', 'area', 'zone', 'status', 'message_type', 'event', 'severity', 'urgency', 'certainty', 'limit',
    ];

    /** NWS allows at most one way of saying where. */
    private const array LOCATION_PARAMETERS = ['point', 'area', 'zone'];

    /** Accepted values, lower-cased, for the enumerated filters. */
    private const array ENUMERATIONS = [
        'status' => ['actual', 'exercise', 'system', 'test', 'draft'],
        'message_type' => ['alert', 'update', 'cancel'],
        'severity' => ['extreme', 'severe', 'moderate', 'minor', 'unknown'],
        'urgency' => ['immediate', 'expected', 'future', 'past', 'unknown'],
        'certainty' => ['observed', 'likely', 'possible', 'unlikely', 'unknown'],
    ];

    private const array GEO_HEADERS = [
        'Content-Type' => 'application/geo+json',
        'Cache-Control' => 'public, max-age=30',
    ];

    public function __construct(private readonly NwsCompatibleAlertsService $alerts) {}

    /**
     * GET /api/nws/alerts/active — as NWS's /alerts/active.
     */
    public function active(Request $request): JsonResponse
    {
        $query = $request->query();

        foreach (array_keys($query) as $parameter) {
            if (! in_array($parameter, self::SUPPORTED_PARAMETERS, true)) {
                return $this->badRequest("The '{$parameter}' parameter is not supported by this server.");
            }
        }

        $locations = array_values(array_intersect(self::LOCATION_PARAMETERS, array_keys($query)));
        if (count($locations) > 1) {
            return $this->badRequest('Only one of point, area and zone may be given; received '.implode(', ', $locations).'.');
        }

        $filters = [];

        try {
            if ($request->has('point')) {
                $filters['point'] = $this->point((string) $request->query('point'));
            }

            if ($request->has('area')) {
                $filters['area'] = $this->codes($request, 'area', '/^[A-Z]{2}$/', 'a two-letter state or marine area code');
            }

            if ($request->has('zone')) {
                $filters['zone'] = $this->codes($request, 'zone', '/^[A-Z]{2}[CZ]\d{3}$/', 'a county or zone id such as TXC121 or GMZ557');
            }

            foreach (self::ENUMERATIONS as $parameter => $allowed) {
                if ($request->has($parameter)) {
                    $filters[$parameter] = $this->enumeration($request, $parameter, $allowed);
                }
            }

            if ($request->has('event')) {
                $filters['event'] = $this->values($request, 'event');
            }

            if ($request->has('limit')) {
                $filters['limit'] = $this->limit((string) $request->query('limit'));
            }
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        try {
            $collection = $this->alerts->activeAlerts($filters);
        } catch (UnresolvablePointException $e) {
            // Not an empty collection: "no alerts" would be a claim about a place
            // nobody could look up.
            return NwsProblem::make(503, 'Service Unavailable', $e->getMessage().' Try again shortly.', ['Retry-After' => 60]);
        }

        return response()->json($collection, 200, self::GEO_HEADERS);
    }

    /**
     * GET /api/nws/alerts/{id} — as NWS's /alerts/{id}.
     */
    public function show(string $id): JsonResponse
    {
        $alert = $this->alerts->alert($id);

        if ($alert === null) {
            return NwsProblem::make(404, 'Not Found', "Alert '{$id}' was not found.");
        }

        return response()->json($alert, 200, self::GEO_HEADERS);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function point(string $value): array
    {
        if (! preg_match('/^\s*(-?\d{1,3}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $value, $m)) {
            throw new \InvalidArgumentException("Parameter 'point' must be latitude,longitude, e.g. 29.3,-94.8.");
        }

        $latitude = (float) $m[1];
        $longitude = (float) $m[2];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException("Parameter 'point' is out of range.");
        }

        return [$latitude, $longitude];
    }

    /**
     * @return list<string>
     */
    private function codes(Request $request, string $parameter, string $pattern, string $expected): array
    {
        $codes = array_map('strtoupper', $this->values($request, $parameter));

        foreach ($codes as $code) {
            if (! preg_match($pattern, $code)) {
                throw new \InvalidArgumentException("'{$code}' is not a valid {$parameter}: expected {$expected}.");
            }
        }

        return $codes;
    }

    /**
     * Enumerated values in the casing Lookout stores them in (`Actual`, `Severe`).
     *
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function enumeration(Request $request, string $parameter, array $allowed): array
    {
        return array_map(function (string $value) use ($parameter, $allowed) {
            $lower = strtolower($value);

            if (! in_array($lower, $allowed, true)) {
                throw new \InvalidArgumentException("'{$value}' is not a valid {$parameter}: expected one of ".implode(', ', $allowed).'.');
            }

            return ucfirst($lower);
        }, $this->values($request, $parameter));
    }

    /**
     * A list parameter, given comma-separated (`status=actual,test`) or repeated with
     * brackets (`status[]=actual&status[]=test`).
     *
     * @return list<string>
     */
    private function values(Request $request, string $parameter): array
    {
        $raw = $request->query($parameter);
        $parts = is_array($raw) ? $raw : explode(',', (string) $raw);

        $values = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $parts), fn ($v) => $v !== ''));

        if ($values === []) {
            throw new \InvalidArgumentException("Parameter '{$parameter}' is empty.");
        }

        return $values;
    }

    private function limit(string $value): int
    {
        if (! ctype_digit($value) || (int) $value < 1 || (int) $value > NwsCompatibleAlertsService::MAX_LIMIT) {
            throw new \InvalidArgumentException("Parameter 'limit' must be a whole number from 1 to ".NwsCompatibleAlertsService::MAX_LIMIT.'.');
        }

        return (int) $value;
    }

    private function badRequest(string $detail): JsonResponse
    {
        return NwsProblem::make(400, 'Bad Request', $detail);
    }
}
