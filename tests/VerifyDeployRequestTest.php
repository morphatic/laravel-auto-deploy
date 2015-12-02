<?php

use Orchestra\Testbench\TestCase;

class VerifyDeployRequestTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    protected function getExec($n)
    {
        // mock the shell exec class's run method
        $exec = \Mockery::mock('AdamBrett\ShellWrapper\Runners\Exec');
        $exec->shouldReceive('run')->times($n)->andReturn('last line of output');
        $exec->shouldReceive('getOutput')->times($n)->andReturn(['output line 1', 'output line 2']);
        $exec->shouldReceive('getReturnValue')->times($n)->andReturn(0);

        return $exec;
    }

    protected function getPackageProviders($app)
    {
        return ['Morphatic\AutoDeploy\AutoDeployServiceProvider'];
    }

    protected function getEnvironmentSetup($app)
    {
        // Setup default config
        $config = include 'config/config.php';
        $app['config']->set('auto-deploy', $config);
        $app['config']->set('auto-deploy.secret', 'yOq5C0gTCvl0hAWZZhQy5bqc8EQQqHjr');
        $app['config']->set('auto-deploy.route', 'D72lZY0W1qf4dlvWDVT1bSz2etDWqU7j');
        $app['config']->set('auto-deploy.TestVerified', [
                'push' => [
                    'webroot' => dirname(__DIR__).'/build/www/mysite.com',
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
            ]
        );

        // Add our UnverifiedOrigin and VerifiedOrigin classes to the list of origins
        include_once 'tests/classes/UnverifiedOrigin.php';
        include_once 'tests/classes/VerifiedOrigin.php';
        $app->bind('Morphatic\AutoDeploy\Origins\OriginInterface', 'Morphatic\AutoDeploy\Origins\UnverifiedOrigin');
        $app->bind('Morphatic\AutoDeploy\Origins\OriginInterface', 'Morphatic\AutoDeploy\Origins\VerifiedOrigin');
        $origins = $app['config']->get('auto-deploy.origins');
        $origins[] = 'UnverifiedOrigin';
        $origins[] = 'VerifiedOrigin';
        $app['config']->set('auto-deploy.origins', $origins);

        // Setup default route
        $app['router']->get('/', function () {
            return 'Hello, World!';
        });

        // Setup webhook response route
        $app['router']->post(
            config('auto-deploy.route'), [
                'as' => 'autodeployroute',
                'uses' => 'Morphatic\AutoDeploy\Controllers\DeployController@index',
            ]
        );
    }

    public function testConfig()
    {
        $this->assertTrue(config('auto-deploy.require-ssl'));
        $this->assertEquals('yOq5C0gTCvl0hAWZZhQy5bqc8EQQqHjr', config('auto-deploy.secret'));
        $this->assertEquals('D72lZY0W1qf4dlvWDVT1bSz2etDWqU7j', config('auto-deploy.route'));
    }

    public function testPassThrough()
    {
        $response = $this->call('GET', '/');
        $this->assertResponseOk();
        $this->assertEquals('Hello, World!', $response->getContent());
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Forbidden. Webhook requests must be sent using SSL.
     */
    public function testInsecureWebhookRequest()
    {
        $uri = $this->app['url']->route('autodeployroute', []);
        $this->call('POST', $uri);
        $this->assertResponseStatus(403);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Forbidden. Could not determine the origin of the request.
     */
    public function testUnknownOriginRequest()
    {
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $this->call('POST', $uri);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Forbidden. Could not verify the origin of the request.
     */
    public function testUnverifiedOriginRequest()
    {
        $server = [
            'HTTP_ORIGIN' => 'Unverified-Webhook',
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $this->call('POST', $uri, [], [], [], $server);
    }

    public function testVerifiedOrigin()
    {
        App::instance('AdamBrett\ShellWrapper\Runners\Exec', $this->getExec(9));
        $server = [
            'HTTP_ORIGIN' => 'Verified-Webhook',
            'HTTP_Event-Type' => 'push',
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $response = $this->call('POST', $uri, [], [], [], $server);
        $this->assertResponseOk();
        $controller = new ReflectionObject($this->app['Morphatic\AutoDeploy\Controllers\DeployController']);
        $origin = $controller->getProperty('origin');
        $origin->setAccessible(true);
        $origin = $origin->getValue($this->app['Morphatic\AutoDeploy\Controllers\DeployController']);
        $this->assertEquals('Morphatic\AutoDeploy\Origins\VerifiedOrigin', get_class($origin));
    }
}
