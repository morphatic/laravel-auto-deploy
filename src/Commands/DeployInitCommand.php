<?php

namespace Morphatic\AutoDeploy\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class DeployInitCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'deploy:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secret key and route name for automated deployments';

    /**
     * Execute the console command.
     */
    public function fire()
    {
        // generate a secret key and route name
        $secret = $this->getRandomKey($this->laravel['config']['app.cipher']);
        $route = $this->getRandomKey($this->laravel['config']['app.cipher']);

        if ($this->option('show')) {
            return $this->line('<comment>Secret: '.$secret.'</comment>'.
                               '<comment>Route: /'.$route.'</comment>');
        }

        $path = base_path('.env');

        if (file_exists($path)) {
            // get the contents of the .env file
            $env_content = file_get_contents($path);

            // check to see if the autodeploy secret has been set
            if (false !== strpos($env_content, 'AUTODEPLOY_SECRET=')) {
                // it exists already, overwrite it
                // TODO: confirm overwrite; add --force option?
                file_put_contents($path, str_replace(
                    'AUTODEPLOY_SECRET='.$this->laravel['config']['auto-deploy.secret'],
                    'AUTODEPLOY_SECRET='.$secret,
                    file_get_contents($path)
                ));
            } else {
                // doesn't exist yet, so add it
                file_put_contents($path, $env_content."\n\n".'AUTODEPLOY_SECRET='.$secret);
            }

            // refresh .env file content
            $env_content = file_get_contents($path);

            // check to see if the autodeploy route has been set
            if (false !== strpos($env_content, 'AUTODEPLOY_ROUTE=')) {
                // it exists already, overwrite it
                // TODO: confirm overwrite; add --force option?
                file_put_contents($path, str_replace(
                    'AUTODEPLOY_ROUTE='.$this->laravel['config']['auto-deploy.route'],
                    'AUTODEPLOY_ROUTE='.$route,
                    file_get_contents($path)
                ));
            } else {
                // doesn't exist yet, so add it
                file_put_contents($path, $env_content."\n".'AUTODEPLOY_ROUTE='.$route);
            }
        } else {
            // create a new .env file and add the necessary keys
            file_put_contents($path, 'AUTODEPLOY_SECRET='.$secret."\n".'AUTODEPLOY_ROUTE='.$route."\n");
        }

        $this->laravel['config']['auto-deploy.secret'] = $secret;
        $this->laravel['config']['auto-deploy.route'] = $route;

        $this->info(
            "Here is the information you'll need to set up your webhook at Github:\n\n
            Payload URL: https://yourdomain.com/$route\n
            Secret: $secret\n\n
            You can display this information again by running `php artisan deploy:info`\n");
    }

    /**
     * Generate random keys for the auto deploy package.
     *
     * @param string $cipher
     *
     * @return string
     */
    protected function getRandomKey($cipher)
    {
        if ($cipher === 'AES-128-CBC') {
            return Str::random(16);
        }

        return Str::random(32);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['show', null, InputOption::VALUE_NONE, 'Simply display the secret and route instead of modifying files.'],
        ];
    }
}
