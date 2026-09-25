<?php

namespace App\Models;

use App\Observers\NwsAlertCountyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([NwsAlertCountyObserver::class])]
class NwsAlertCounty extends Model
{
    protected $fillable = [
        'alert_id',
        'county_ugc',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(NwsAlert::class, 'alert_id', 'id');
    }
}
