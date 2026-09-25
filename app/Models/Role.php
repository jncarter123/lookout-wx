<?php

namespace App\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsActivity;

    protected $table = 'roles';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
