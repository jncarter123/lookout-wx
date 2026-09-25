<?php

use App\Jobs\PollNwsAlerts;
use App\Jobs\PruneExpiredAlerts;

// Run NWS polling every minute (schedule the job directly)
Schedule::job(new PollNwsAlerts)
    ->everyMinute()
    ->withoutOverlapping();

// Prune alerts expired more than 24 hours ago, runs daily
Schedule::job(new PruneExpiredAlerts)
    ->daily()
    ->withoutOverlapping();
