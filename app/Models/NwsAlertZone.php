<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NwsAlertZone extends Model
{
    protected $fillable = [
        'alert_id',
        'zone_id',
        'zone_kind',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(NwsAlert::class, 'alert_id', 'id');
    }
}
