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
        $message = 'You need to run `php artisan deploy:init` first.';
        if (file_exists($path)) {
            // get the contents of the .env file
            $env_content = file_get_contents($path);

            // get the values of the two keys, and inform the user of the necessary information
            if (preg_match('/AUTODEPLOY_SECRET=(\S+)/', $env_content, $secret) &&
                preg_match('/AUTODEPLOY_ROUTE=(\S+)/', $env_content, $route)) {
                $url = parse_url(config('app.url'), PHP_URL_HOST);
                $message = "Here is the information you'll need to set up your webhook:\n\n".
                           "Payload URL: <comment>https://$url/{$route[1]}</comment>\n".
                           "Secret: <comment>{$secret[1]}</comment>\n\n".
                           "You can display this information again by running `php artisan deploy:info`\n";
            }
        }
        $this->info($message);
    }
}
