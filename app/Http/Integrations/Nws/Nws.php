<?php

namespace App\Http\Integrations\Nws;

use App\Http\Integrations\Nws\Middleware\NwsHttpCacheRequestMiddleware;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Plugins\AcceptsJson;

class Nws extends Connector
{
    use AcceptsJson;

    public function boot(PendingRequest $pendingRequest): void
    {
        // Register on the pending request: connector middleware has already been
        // merged into it by the time boot() runs.
        $pendingRequest->middleware()->onRequest(new NwsHttpCacheRequestMiddleware);
    }

    public function resolveBaseUrl(): string
    {
        return config('wxalerts.nws.baseurl');
    }

    protected function defaultHeaders(): array
    {
        return [
            // NWS strongly prefers a real, descriptive User-Agent.
            'User-Agent' => config('wxalerts.nws.user_agent'),
        ];
    }
}