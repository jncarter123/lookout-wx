<?php

namespace App\Http\Integrations\Nws\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class PointsMetadata extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
    ) {}

    public function resolveEndpoint(): string
    {
        // /points/lat,lon
        return '/points/'.$this->latitude.','.$this->longitude;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/geo+json',
        ];
    }
}
