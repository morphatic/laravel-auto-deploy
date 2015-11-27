<?php

namespace Morphatic\AutoDeploy\Commands;

use Illuminate\Console\Command;

class DeployInfoCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'deploy:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information necessary to set up Github webhook';

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            // get the contents of the .env file
            $env_content = file_get_contents($path);

            // get the values of the two keys
            preg_match('/AUTODEPLOY_SECRET=(\S+)/', $env_content, $secret);
            preg_match('/AUTODEPLOY_ROUTE=(\S+)/', $env_content, $route);

            $secret = isset($secret[1]) ? $secret[1] : null;
            $route = isset($route[1]) ? $route[1] : null;

            if ($secret && $route) {
                $message = "Here is the information you'll need to set up your webhook at Github:\n\n".
                           "Payload URL: https://yourdomain.com/$route\n".
                           "Secret: $secret\n\n".
                           "You can display this information again by running `php artisan deploy:info`\n";
            }
        } else {
            // create a new .env file and add the necessary keys
            $message = 'You need to run `php artisan deploy:init` first.';
        }
        $this->info($message);
    }
}
