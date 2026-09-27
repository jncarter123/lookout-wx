<?php

return [
    'nws' => [
        'baseurl' => env('NWS_API_BASEURL'),
        'user_agent' => env('NWS_API_USER_AGENT'),
    ],

    /*
    | The public, NWS-compatible endpoints under /api/nws. They answer in the NWS API's
    | own shape, so an application already calling api.weather.gov can switch to Lookout
    | by changing its base URL alone.
    */
    'nws_compatible' => [
        // Requests per minute, per client IP.
        'rate_limit' => (int) env('NWS_COMPAT_RATE_LIMIT', 60),

        // How long an identical query is answered from cache. Lookout polls NWS every
        // minute, so a short window costs no freshness worth having.
        'cache_seconds' => (int) env('NWS_COMPAT_CACHE_SECONDS', 30),
    ],
];
