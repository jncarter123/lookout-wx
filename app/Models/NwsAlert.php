<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NwsAlert extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'event',
        'headline',
        'severity',
        'certainty',
        'urgency',
        'status',
        'message_type',
        'category',
        'raw',
        'sent',
        'effective',
        'onset',
        'expires',
        'ends',
        'nws_updated_at',
        'removed_from_feed_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'sent' => 'datetime',
        'effective' => 'datetime',
        'onset' => 'datetime',
        'expires' => 'datetime',
        'ends' => 'datetime',
        'nws_updated_at' => 'datetime',
        'removed_from_feed_at' => 'datetime',
    ];

    /**
     * Alerts still present in the NWS active feed, excluding cancellation notices.
     * Callers still apply their own expires/ends time checks.
     */
    public function scopeInActiveFeed(Builder $query): Builder
    {
        return $query
            ->whereNull('removed_from_feed_at')
            ->where(function (Builder $q) {
                $q->whereNull('message_type')->orWhere('message_type', '!=', 'Cancel');
            });
    }

    /**
     * Alerts in the active feed that have not yet expired or ended.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->inActiveFeed()
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expires')->orWhere('expires', '>', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends')->orWhere('ends', '>', $now);
            });
    }

    public function isActive(): bool
    {
        $now = now();

        return $this->removed_from_feed_at === null
            && $this->message_type !== 'Cancel'
            && ($this->expires === null || $this->expires->gt($now))
            && ($this->ends === null || $this->ends->gt($now));
    }

    public function counties(): HasMany
    {
        return $this->hasMany(NwsAlertCounty::class, 'alert_id', 'id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(NwsAlertZone::class, 'alert_id', 'id');
    }
}
