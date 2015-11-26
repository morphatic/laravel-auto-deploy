<?php

namespace Morphatic\AutoDeploy;

use Illuminate\Support\ServiceProvider;

class AutoDeployServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerDeployInitCommand();
        $this->registerRoutes();
    }

    private function registerDeployInitCommand()
    {
        $this->app->singleton('command.morphatic.deployinit', function ($app) {
            return $app['Morphatic\AutoDeploy\Commands\DeployInitCommand'];
        });

        $this->commands('command.morphatic.deployinit');
    }

    private function registerRoutes()
    {
        // only register routes if the secret route has been set
        if (!empty($this->app->config('auto-deploy.route'))) {
            include_once __DIR__.'routes.php';
        } else {
            // throw an exception? display a warning?
        }
    }
}
