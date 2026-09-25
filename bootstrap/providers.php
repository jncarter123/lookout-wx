<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    PermissionServiceProvider::class,
];
