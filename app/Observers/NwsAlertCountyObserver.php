<?php

namespace App\Observers;

use App\Models\NwsAlertCounty;
use App\Services\NwsAlertsApiService;

readonly class NwsAlertCountyObserver
{
    public function __construct(
        private NwsAlertsApiService $api,
    ) {}

    public function created(NwsAlertCounty $model): void
    {
        $this->api->invalidateCounty($model->county_ugc);
    }

    public function deleted(NwsAlertCounty $model): void
    {
        $this->api->invalidateCounty($model->county_ugc);
    }

    public function updated(NwsAlertCounty $model): void
    {
        // In case county_ugc ever changes (rare, but safe)
        $this->api->invalidateCounty($model->county_ugc);

        $original = $model->getOriginal('county_ugc');
        if (is_string($original) && $original !== $model->county_ugc) {
            $this->api->invalidateCounty($original);
        }
    }
}
