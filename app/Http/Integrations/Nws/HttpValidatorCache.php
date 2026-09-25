<?php

namespace App\Http\Integrations\Nws;

use Illuminate\Support\Facades\Cache;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

/**
 * Stores ETag / Last-Modified validators for NWS requests.
 *
 * Validators are only written when the caller explicitly calls remember()
 * after it has finished processing a response. Storing them any earlier
 * means a failed processing attempt is followed by a 304 on the next
 * request, and that version of the resource is never processed.
 */
class HttpValidatorCache
{
    private const string CACHE_PREFIX = 'nws:httpcache:';
    private const int CACHE_TTL_SECONDS = 60 * 60 * 24 * 7; // keep validators around for a week

    /**
     * Add conditional request headers if we have validators for this request.
     */
    public function applyTo(PendingRequest $pendingRequest): void
    {
        $key = $this->requestKey($pendingRequest);

        $etag = Cache::get($this->cacheKey($key, 'etag'));
        $lastModified = Cache::get($this->cacheKey($key, 'last_modified'));

        if (is_string($etag) && $etag !== '') {
            $pendingRequest->headers()->add('If-None-Match', $etag);
        }

        if (is_string($lastModified) && $lastModified !== '') {
            $pendingRequest->headers()->add('If-Modified-Since', $lastModified);
        }
    }

    /**
     * Store the response's validators. Call only once the response has been fully processed.
     */
    public function remember(Response $response): void
    {
        if ($response->status() !== 200) {
            return;
        }

        $key = $this->requestKey($response->getPendingRequest());

        $etag = $response->header('ETag');
        $lastModified = $response->header('Last-Modified');

        if (is_string($etag) && $etag !== '') {
            Cache::put($this->cacheKey($key, 'etag'), $etag, self::CACHE_TTL_SECONDS);
        }

        if (is_string($lastModified) && $lastModified !== '') {
            Cache::put($this->cacheKey($key, 'last_modified'), $lastModified, self::CACHE_TTL_SECONDS);
        }
    }

    /**
     * URL plus sorted query string, built identically for requests and responses.
     */
    private function requestKey(PendingRequest $pendingRequest): string
    {
        $query = array_filter($pendingRequest->query()->all(), static fn ($v) => $v !== null);
        ksort($query);

        return $pendingRequest->getUrl() . ($query === [] ? '' : '?' . http_build_query($query));
    }

    private function cacheKey(string $requestKey, string $field): string
    {
        return self::CACHE_PREFIX . $field . ':' . sha1($requestKey);
    }
}
