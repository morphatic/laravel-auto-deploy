<?php

use Orchestra\Testbench\TestCase;

class AutoDeployCommandsTest extends TestCase
{
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
    }

    public function testDeployInfoBeforeInit()
    {
        $code = $this->artisan('deploy:info');
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $this->assertEquals(0, $code);
        $this->assertEquals("You need to run `php artisan deploy:init` first.\n", $info);
    }

    public function testDeployInit()
    {
        $code = $this->artisan('deploy:init');
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $secret = config('auto-deploy.secret');
        $route = config('auto-deploy.route');
        $msg = "Here is the information you'll need to set up your webhook at Github:\n\n".
               "Payload URL: https://yourdomain.com/$route\n".
               "Secret: $secret\n\n".
               "You can display this information again by running `php artisan deploy:info`\n\n";
        $this->assertEquals(0, $code);
        $this->assertEquals($msg, $info);

        return [$secret, $route];
    }

    /**
     * @depends testDeployInit
     */
    public function testDeployInfoAfterInit($args)
    {
        $code = $this->artisan('deploy:info');
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $msg = "Here is the information you'll need to set up your webhook at Github:\n\n".
               "Payload URL: https://yourdomain.com/{$args[1]}\n".
               "Secret: {$args[0]}\n\n".
               "You can display this information again by running `php artisan deploy:info`\n\n";
        $this->assertEquals(0, $code);
        $this->assertEquals($msg, $info);
    }

    public function testDeployInitShow()
    {
        $code = $this->artisan('deploy:init', ['--show' => true]);
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $msg = "~Here is the information you'll need to set up your webhook at Github:\n\n".
               "Payload URL: https://yourdomain.com/\S+\n".
               "Secret: \S+\n\n".
               "You can display this information again by running `php artisan deploy:info`\n\n~";
        $this->assertEquals(0, $code);
        $this->assertRegExp($msg, $info);
    }

    public function testDeployReInit()
    {
        $code = $this->artisan('deploy:init');
        $info = $this->app['Illuminate\Contracts\Console\Kernel']->output();
        $secret = config('auto-deploy.secret');
        $route = config('auto-deploy.route');
        $msg = "Here is the information you'll need to set up your webhook at Github:\n\n".
               "Payload URL: https://yourdomain.com/$route\n".
               "Secret: $secret\n\n".
               "You can display this information again by running `php artisan deploy:info`\n\n";
        $this->assertEquals(0, $code);
        $this->assertEquals($msg, $info);
    }
}
