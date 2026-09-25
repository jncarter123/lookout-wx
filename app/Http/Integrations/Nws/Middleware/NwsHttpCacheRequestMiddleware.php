<?php

namespace App\Http\Integrations\Nws\Middleware;

use App\Http\Integrations\Nws\HttpValidatorCache;
use Saloon\Contracts\RequestMiddleware;
use Saloon\Http\PendingRequest;

/**
 * Adds conditional headers (If-None-Match / If-Modified-Since) from stored validators.
 * Validators are stored by callers via HttpValidatorCache::remember() after processing succeeds.
 */
class NwsHttpCacheRequestMiddleware implements RequestMiddleware
{
    public function __construct(
        private readonly HttpValidatorCache $validators = new HttpValidatorCache,
    ) {}

    public function __invoke(PendingRequest $pendingRequest): void
    {
        $this->validators->applyTo($pendingRequest);
    }
}
