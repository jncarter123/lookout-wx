<?php

namespace App\Http\Integrations\Nws\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ActiveAlertsByPoint extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
    ) {}

    public function resolveEndpoint(): string
    {
        // /alerts/active?point=lat,lon
        return '/alerts/active';
    }

    protected function defaultQuery(): array
    {
        return [
            'point' => $this->latitude.','.$this->longitude,
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/geo+json',
        ];
    }
}
