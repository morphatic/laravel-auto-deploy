<?php

namespace Morphatic\AutoDeploy\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class DeployInitCommand extends Command
{
    /**
     * The options available with this command.
     *
     * @var string
     */
    protected $signature = 'deploy:init
                            {--show : Only display the current values (do NOT use with --force)}
                            {--force : Force current values to be overwritten}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secret key and route name for automated deployments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set necessary variables
        $path = base_path('.env');
        $url = parse_url(config('app.url'), PHP_URL_HOST);
        $msg = "Here is the information you'll need to set up your webhooks:\n\n".
               "  Payload URL: <comment>https://$url/%s</comment>\n".
               "  Secret Key:  <comment>%s</comment>\n\n".
               "You can display this information again by running `php artisan deploy:info`\n";
        $conf = 'Are you sure you want to overwrite the existing keys?';
        $show = $this->option('show');
        $over = $this->option('force');
        $file = file_exists($path) ? file_get_contents($path) : '';
        $set = preg_match('/^(?=.*\bAUTODEPLOY_SECRET=(\S+)\b)(?=.*\bAUTODEPLOY_ROUTE=(\S+)\b).*$/s', $file, $keys);

        // Step 0: Handle edge cases
        if ($show and $over) {
            return $this->error("You can't use --force and --show together!");
        }

        if ($show and !$set) {
            if ($this->confirm("You don't have any values to show yet. Would you like to create them now?", true)) {
                $show = false;
            } else {
                return;
            }
        }

        // Step 1: Retrieve or Generate?
        if (($set and $show) || ($set and !$show and !$over and !$this->confirm($conf))) {
            // Retrieve
            $secret = $keys[1];
            $route = $keys[2];
        } else {
            // Generate
            $secret = $this->getRandomKey($this->laravel['config']['app.cipher']);
            $route = $this->getRandomKey($this->laravel['config']['app.cipher']);
            if (!$show) {
                if ($file) {
                    if ($set) {
                        // Replace existing keys
                        file_put_contents(
                            $path,
                            preg_replace(
                                '/AUTODEPLOY_SECRET=\S+\n/',
                                "AUTODEPLOY_SECRET=$secret\n",
                                $file
                            )
                        );
                        file_put_contents(
                            $path,
                            preg_replace(
                                '/AUTODEPLOY_ROUTE=\S+\n/',
                                "AUTODEPLOY_ROUTE=$route\n",
                                file_get_contents($path)
                            )
                        );
                    } else {
                        // Append to existing environment variables
                        file_put_contents($path, $file."\n\nAUTODEPLOY_SECRET=$secret\nAUTODEPLOY_ROUTE=$route\n");
                    }
                } else {
                    // Create new .env file
                    file_put_contents($path, "AUTODEPLOY_SECRET=$secret\nAUTODEPLOY_ROUTE=$route\n");
                }
            }
        }

        // Step 2: Set the values in the global config
        $this->laravel['config']['auto-deploy.secret'] = $secret;
        $this->laravel['config']['auto-deploy.route'] = $route;

        // Step 3: Display the keys
        return $this->line(sprintf($msg, $route, $secret));
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
}
