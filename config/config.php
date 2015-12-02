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

    'require-ssl' => true,
    'notify' => 'morgan.benton@gmail.com',
    'secret' => env('AUTODEPLOY_SECRET'),
    'route' => env('AUTODEPLOY_ROUTE'),
    'origins' => [
        'Github',
    ],
    'Github' => [
        'push' => [
            'webroot' => '/var/www/staging.mysite.com',
            'steps' => [
                'backupDatabase',
                'pull',
                'composer',
                'npm',
                'migrate',
                'seed',
                'deploy',
            ],
        ],
        'create' => [
            'webroot' => '/var/www/staging.mysite.com',
            'steps' => [
                'backupDatabase',
                'pull',
                'composer',
                'npm',
                'migrate',
                'seed',
                'deploy',
            ],
        ],
        'release' => [
            'webroot' => '/var/www/mysite.com',
            'steps' => [
                'backupDatabase',
                'pull',
                'composer',
                'npm',
                'migrate',
                'seed',
                'deploy',
            ],
        ],
    ],
];
