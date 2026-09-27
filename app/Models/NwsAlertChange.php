<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NwsAlertChange extends Model
{
    public const string UPSERTED = 'upserted';

    public const string REMOVED = 'removed';

    const UPDATED_AT = null;

    protected $fillable = [
        'alert_id',
        'type',
    ];

    /**
     * @param  string[]  $alertIds
     */
    public static function record(array $alertIds, string $type): void
    {
        $now = now();

        foreach (array_chunk($alertIds, 500) as $chunk) {
            static::query()->insert(array_map(
                static fn (string $id) => ['alert_id' => $id, 'type' => $type, 'created_at' => $now],
                $chunk
            ));
        }
    }
}
