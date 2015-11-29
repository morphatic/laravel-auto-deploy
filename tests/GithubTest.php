<?php

use Orchestra\Testbench\TestCase;

class GithubTest extends TestCase
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

        // Setup webhook response route
        $app['router']->post(
            config('auto-deploy.route'), [
                'as' => 'autodeployroute',
                'uses' => 'Morphatic\AutoDeploy\Controllers\DeployController@index',
            ]
        );
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Forbidden. Could not verify the origin of the request.
     */
    public function testUnverifiedGithubRequest()
    {
        $server = [
            'HTTP_User-Agent' => 'GitHub-Hookshot 12345',
            'REMOTE_ADDR' => '192.30.252.0',
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $this->call('POST', $uri, [], [], [], $server, 'hello');
    }

    public function testVerifiedGithubRequest()
    {
        $secret = 'sha1='.hash_hmac('sha1', 'hello', config('auto-deploy.secret'));
        $server = [
            'HTTP_User-Agent' => 'GitHub-Hookshot 12345',
            'REMOTE_ADDR' => '192.30.252.0',
            'HTTP_X-Hub-Signature' => $secret,
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $response = $this->call('POST', $uri, [], [], [], $server, 'hello');
        $this->assertResponseOk();
    }
}
