<?php

use Orchestra\Testbench\Testcase;

class VerifyDeployRequestTest extends TestCase
{
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

        // Add our UnverifiedOrigin and VerifiedOrigin classes to the list of origins
        include_once 'tests/classes/UnverifiedOrigin.php';
        include_once 'tests/classes/VerifiedOrigin.php';
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
        $server = [
            'HTTP_ORIGIN' => 'Verified-Webhook',
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $response = $this->call('POST', $uri, [], [], [], $server);
        $this->assertResponseOk();
    }
}
