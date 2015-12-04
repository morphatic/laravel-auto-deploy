<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Auto Deploy Settings
     |--------------------------------------------------------------------------
     |
     | It is important to read through these settings carefully. This package
     | will NOT work out-of-the-box and MUST be configured. Please follow the
     | installation steps at https://github.com/morphatic/laravel-auto-deploy
     | and also pay close attention to the config settings in this file.
     |
     */

    /*
     |--------------------------------------------------------------------------
     | Require SSL?
     |--------------------------------------------------------------------------
     |
     | This is enabled by default, and it is STRONGLY recommended that you leave
     | this enabled. If you don't have SSL enabled on your server already, you
     | can get a FREE SSL certificate from https://letsencrypt.org. You can 
     | also use a self-signed certificate, but make sure that you configure
     | the webhook to "Disable SSL verification" or the webhook request will 
     | never reach your server.
     |
     */

    'require-ssl' => true,

    /*
     |--------------------------------------------------------------------------
     | Email Notifications
     |--------------------------------------------------------------------------
     |
     | To what email address(es) should deployment logs be sent? By default,
     | Auto Deploy will use the email settings configured in config/mail.php
     | and send messages to the address in the 'from' field configured there.
     | If your Laravel app is not set up to send email, no emails will be sent.
     | You can set this field to an individual address, e.g.
     |
     |    'notify' => 'admin@mysite.com',
     |
     | Or you can set it to an array of addresses, e.g.
     |
     |    'notify' => ['person1@mysite.com', 'person2@mysite.com'],
     |
     | Set this field to null if you would like to disable email notifications.
     |
     */

    'notify' => config('mail.from.address'),

    /*
     |--------------------------------------------------------------------------
     | Secret Webhook Route
     |--------------------------------------------------------------------------
     |
     | Since the point of this package is to run terminal commands on your
     | server, it is VERY IMPORTANT that the process is secure. You don't want
     | a random agent to trigger a deployment on your server! One way to do
     | that is to keep that URL that webhook providers use to ping your site
     | a secret. Auto Deploy generates a random string that is stored in your
     | .env file with the key AUTODEPLOY_ROUTE. That value is referenced here.
     | You should NEVER copy the actual value into this file, nor store it in
     | any publicly accessible location (the same as ANY value in your .env).
     |
     | To generate the AUTODEPLOY_ROUTE for your app, from the command line run:
     |
     |    php artisan deploy:init
     |
     | This will automatically generate a new, unique, random string that you
     | will use in the URL you give to the webhook provider. If you need to
     | know the URL, you can type the following from the command line:
     |
     |    php artisan deploy:info
     |
     | If at any point you change the value in your .env, you will need to
     | update the URL with your webhook provider accordingly.
     |
     */

    'route' => env('AUTODEPLOY_ROUTE'),

    /*
     |--------------------------------------------------------------------------
     | Secret Key
     |--------------------------------------------------------------------------
     |
     | Some webhook providers, e.g. Github, ask you to provide them with a
     | secret key that they will use to create a special hash value, that gets
     | used by Auto Deploy to verify the authenticity of the webhook request.
     |
     | Like the secret route, the AUTODEPLOY_SECRET is generated for you when
     | you run the artisan command `deploy:init`. Like AUTODEPLOY_ROUTE, you
     | should NEVER allow this value to become public.
     |
     | In the event that either your secret route or secret key do get leaked
     | to the public, you can regenerate them by running:
     |
     |    php artisan deploy:init --force
     |
     | This will OVERWRITE the previous values in your .env file. You will need
     | to update the values with your webhook provider accordingly.
     |
     */

    'secret' => env('AUTODEPLOY_SECRET'),

    /*
     |--------------------------------------------------------------------------
     | Webhook Origins
     |--------------------------------------------------------------------------
     |
     | Where do your webhooks come from? Although unusual, it is possible to set
     | up this package to listen for webhooks from more than one source. Right
     | now, only Github is supported. If you would like to create another source
     | you can write a new class in the Morphatic\AutoDeploy\Origins namespace,
     | subclassing AbstractOrigin and implementing the OriginInterface. You can
     | Take a look at the Morphatic\AutoDeploy\Origins\Github class as an 
     | example.
     |
     | Currently Supported: 'Github'
     |
     */

    'origins' => [
        'Github',
    ],

    /*
     |--------------------------------------------------------------------------
     | Configure Webhook Events
     |--------------------------------------------------------------------------
     |
     | For each origin in 'origins' above you should configure at least one kind
     | of event to listen for. By default, Auto Deploy listens for 'push' events
     | and when it receives one, it will attempt to execute the list of steps
     | listed beneath it, and deploy your Laravel app to the 'webroot' directory
     | on your web server.
     |
     | Currently, Auto Deploy only supports updating a Laravel app that is on 
     | the same server as the app receiving the webhooks, although future 
     | versions of this package may support remote deployments as well. As such
     | The 'webhook' value for each event should be set to the absolute path to
     | the website that is registered with your webserver (e.g. Apanche, Nginx).
     |
     | The 'steps' field should contain an array with a list of the deploy steps
     | you would like Auto Deploy to perform for you in the order you would like
     | them performed. The actions supported are listed below.
     |
     | Supported Actions: 
     |
     |    'backupDatabase', 'pull', 'copyEnv', 'composer', 'npm', 'migrate',
     |    'seed', 'deploy'
     |
     */

    'Github' => [
        'push' => [
            'webroot' => '/var/www/staging.mysite.com',
            'steps' => [
                'backupDatabase',
                'pull',
                'copyEnv',
                'composer',
                'npm',
                'migrate',
                'seed',
                'deploy',
            ],
        ],
    ],
];
