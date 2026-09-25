<?php

namespace App\Http\Integrations\Nws\Requests;

use Illuminate\Support\Facades\Cache;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;

class AlertByUrl extends Request
{
    use HasRateLimits;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $url,
    ) {}

    public function resolveEndpoint(): string
    {
        $base = rtrim(config('wxalerts.nws.baseurl'), '/');
        if (str_starts_with($this->url, $base)) {
            return substr($this->url, strlen($base));
        }

        return $this->url;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/geo+json',
        ];
    }

    /**
     * Shared across all processing workers. Waits (rather than throws) when the limit
     * is hit: a thrown limit would fail the job and drop the alert until the feed changes.
     */
    protected function resolveLimits(): array
    {
        return [
            Limit::allow(5)->everySeconds(seconds: 1)->sleep(),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store('redis'));
    }
}