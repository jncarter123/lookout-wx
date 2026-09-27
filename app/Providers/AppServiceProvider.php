<?php

namespace App\Providers;

use App\Http\Responses\NwsProblem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureProxies();
        $this->configureRateLimits();
    }

    /**
     * The NWS-compatible endpoints are public, as the NWS API is, so they are limited
     * per client IP instead of per token. The refusal is an NWS-style problem, so a
     * client written against api.weather.gov reads it as it would NWS's own 429.
     */
    protected function configureRateLimits(): void
    {
        RateLimiter::for('nws-compatible', function (Request $request) {
            return Limit::perMinute(config('wxalerts.nws_compatible.rate_limit'))
                ->by($request->ip())
                ->response(fn (Request $request, array $headers) => NwsProblem::make(
                    429,
                    'Too Many Requests',
                    'Rate limit exceeded. Retry after '.($headers['Retry-After'] ?? 60).' seconds.',
                    $headers,
                ));
        });
    }

    /**
     * Behind a TLS-terminating proxy (as in the Docker setup), trust its
     * X-Forwarded-* headers and generate https URLs, or browsers block the
     * assets and Livewire requests as mixed content.
     */
    protected function configureProxies(): void
    {
        $proxies = config('app.trusted_proxies');

        if (filled($proxies)) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
            );
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
