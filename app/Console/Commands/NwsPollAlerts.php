<?php

namespace App\Console\Commands;

use App\Jobs\PollNwsAlerts;
use Illuminate\Console\Command;

class NwsPollAlerts extends Command
{
    protected $signature = 'nws:poll-alerts {--area= : Two-letter state code (e.g. TX) to limit feed}';
    protected $description = 'Poll NWS active alerts Atom feed and dispatch jobs for new/updated alerts';

    public function handle(): int
    {
        PollNwsAlerts::dispatchSync(
            area: $this->option('area') ?: null,
        );

        return self::SUCCESS;
    }
}