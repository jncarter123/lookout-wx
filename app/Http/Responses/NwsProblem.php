<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * An error in the NWS API's format (RFC 7807 `application/problem+json`, with the
 * `correlationId` NWS adds), so a client written against api.weather.gov handles
 * Lookout's errors the way it already handles NWS's.
 */
class NwsProblem
{
    /**
     * @param  array<string, string|int>  $headers
     */
    public static function make(int $status, string $title, string $detail, array $headers = []): JsonResponse
    {
        return response()->json([
            'correlationId' => (string) Str::uuid(),
            'title' => $title,
            'type' => 'about:blank',
            'status' => $status,
            'detail' => $detail,
            'instance' => request()->fullUrl(),
        ], $status, ['Content-Type' => 'application/problem+json', ...$headers]);
    }
}
