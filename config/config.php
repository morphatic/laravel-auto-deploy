<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Auto Deploy Settings
     |--------------------------------------------------------------------------
     |
     | 
     |
     */

    'requre-ssl' => true,
    'secret' => env('AUTODEPLOY_SECRET'),
    'route' => env('AUTODEPLOY_ROUTE'),
    'origins' => [
        'Github',
    ],
    'servers' => [
        'staging' => [],
        'production' => [
            'app_dir' => '/var/www/portphil.io/',
        ],
    ],

    'events' => [
        'push' => [],
        'create' => [],
        'release' => [],
    ],
];
