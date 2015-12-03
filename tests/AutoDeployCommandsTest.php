<?php

use Orchestra\Testbench\TestCase;

class AutoDeployCommandsTest extends TestCase
{
    /**
     * Template for messages to be returned from deploy commands.
     *
     * @var string
     */
    private $msg;

    protected function getPackageProviders($app)
    {
        return ['Morphatic\AutoDeploy\AutoDeployServiceProvider'];
    }

    public static function tearDownAfterClass()
    {
        $env_file = base_path('.env');
        if (file_exists($env_file)) {
            unlink($env_file);
        }
    }

    protected function getEnvironmentSetup($app)
    {
        // Setup default config
        $config = include 'config/config.php';
        $app['config']->set('auto-deploy', $config);
        $url = parse_url($app['config']->get('app.url'), PHP_URL_HOST);
        $this->msg = "Here is the information you'll need to set up your webhooks:\n\n".
                     "  Payload URL: https://$url/%s\n".
                     "  Secret Key:  %s\n\n".
                     "You can display this information again by running `php artisan deploy:info`\n\n";
    }

    /*
    public function testDeployInfoBeforeInit()
    {
        $code = $this->artisan('deploy:info', ['--no-interaction' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $route = $this->app['config']->get('auto-deploy.route');
        $secret = $this->app['config']->get('auto-deploy.secret');
        $this->assertEquals(0, $code);
        $msg = " You don't have any values to show yet. Would you like to create them now? (yes/no) [yes]:\n>\n";
        $this->assertEquals($msg.sprintf($this->msg, $route, $secret), $info);
    }
    */

    public function testDeployInit()
    {
        $code = $this->artisan('deploy:init', ['--no-interaction' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $route = $this->app['config']->get('auto-deploy.route');
        $secret = $this->app['config']->get('auto-deploy.secret');
        $this->assertEquals(0, $code);
        $this->assertEquals(sprintf($this->msg, $route, $secret), $info);
    }

    public function testDeployInfoAfterInit()
    {
        $code = $this->artisan('deploy:info', ['--no-interaction' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $route = $this->app['config']->get('auto-deploy.route');
        $secret = $this->app['config']->get('auto-deploy.secret');
        $this->assertEquals(0, $code);
        $this->assertEquals(sprintf($this->msg, $route, $secret), $info);
    }

    public function testDeployInitShow()
    {
        $code = $this->artisan('deploy:init', ['--show' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $route = $this->app['config']->get('auto-deploy.route');
        $secret = $this->app['config']->get('auto-deploy.secret');
        $this->assertEquals(0, $code);
        $this->assertEquals(sprintf($this->msg, $route, $secret), $info);
    }

    public function testDeployInitForce()
    {
        $code = $this->artisan('deploy:init', ['--force' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $route = $this->app['config']->get('auto-deploy.route');
        $secret = $this->app['config']->get('auto-deploy.secret');
        $this->assertEquals(0, $code);
        $this->assertEquals(sprintf($this->msg, $route, $secret), $info);
    }

    public function testDeployInitForceShow()
    {
        $code = $this->artisan('deploy:init', ['--force' => true, '--show' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $this->assertEquals(0, $code);
        $this->assertEquals("You can't use --force and --show together!\n", $info);
    }
}
