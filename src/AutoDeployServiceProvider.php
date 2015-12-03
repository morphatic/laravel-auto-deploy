<?php

namespace Morphatic\AutoDeploy;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class AutoDeployServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Kernel $kernel)
    {
        $this->registerMiddleware($kernel);
        $this->registerRoutes();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerDeployCommands();
        $this->registerDeployOrigins();
        $this->registerDeployController();
    }

    private function registerConfig()
    {
        $config = realpath(__DIR__.'/../config/config.php');
        $this->publishes([
            $config => config_path('auto-deploy.php'),
        ]);
    }

    private function registerDeployCommands()
    {
        $this->app->singleton('command.morphatic.deployinit', function ($app) {
            return $app['Morphatic\AutoDeploy\Commands\DeployInitCommand'];
        });

        $this->app->singleton('command.morphatic.deployinfo', function ($app) {
            return $app['Morphatic\AutoDeploy\Commands\DeployInfoCommand'];
        });

        $this->commands('command.morphatic.deployinit');
        $this->commands('command.morphatic.deployinfo');
    }

    private function registerDeployOrigins()
    {
        $originTypes = ['Github'];
        foreach ($originTypes as $ot) {
            $this->app->bind('Morphatic\AutoDeploy\Origins\OriginInterface', 'Morphatic\AutoDeploy\Origins\\'.$ot);
        }
    }

    private function registerDeployController()
    {
        $this->app->make('Morphatic\AutoDeploy\Controllers\DeployController');
    }

    private function registerMiddleware(Kernel $kernel)
    {
        // add our middleware at the beginning, before CSRF checking
        $kernel->prependMiddleware(Middleware\VerifyDeployRequest::class);
    }

    private function registerRoutes()
    {
        // only register routes if the secret route has been set
        if (!empty($this->app['config']->get('auto-deploy.route'))) {
            // register the route
            include_once __DIR__.'/routes.php';
        } else {
            // throw an exception? display a warning?
        }
    }
}
