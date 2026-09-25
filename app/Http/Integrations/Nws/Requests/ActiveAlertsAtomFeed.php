<?php

namespace App\Http\Integrations\Nws\Requests;

use Illuminate\Support\Facades\Cache;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;

class ActiveAlertsAtomFeed extends Request
{
    use HasRateLimits;

    protected Method $method = Method::GET;

    public function __construct(
        private readonly ?string $area,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/alerts/active.atom';
    }

    protected function defaultQuery(): array
    {
        return [
            'area' => $this->area,
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/atom+xml',
        ];
    }

    protected function resolveLimits(): array
    {
        return [
            Limit::allow(1)->everySeconds(seconds: 30),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store('redis'));
    }
}
