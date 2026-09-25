<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marine areas
    |--------------------------------------------------------------------------
    | Central source of truth for:
    | - UI labels (dropdown)
    | - zone prefixes used for filtering alerts (NWS zone IDs)
    */
    'areas' => [
        'AL' => [
            'label' => 'Alaska',
            'zone_prefixes' => ['PKZ', 'PHZ', 'PMZ', 'PNZ'],
        ],
        'AT' => [
            'label' => 'Atlantic',
            'zone_prefixes' => ['AMZ', 'ANZ'],
        ],
        'GL' => [
            'label' => 'Great Lakes',
            'zone_prefixes' => ['LEZ', 'LHZ', 'LMZ', 'LOZ', 'LSZ'],
        ],
        'GM' => [
            'label' => 'Gulf of Mexico',
            'zone_prefixes' => ['GMZ'],
        ],
        'PA' => [
            'label' => 'Eastern Pacific',
            'zone_prefixes' => ['PZZ'],
        ],
        'PI' => [
            'label' => 'Central/Western Pacific',
            'zone_prefixes' => ['PHZ'],
        ],
    ],
];