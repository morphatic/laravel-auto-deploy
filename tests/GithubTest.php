<?php

use Orchestra\Testbench\TestCase;

class GithubTest extends TestCase
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
        $app['config']->set('auto-deploy.Github.push.webroot', dirname(__DIR__).'/build/www/mysite.com');

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
        App::instance('AdamBrett\ShellWrapper\Runners\Exec', $this->getExec(9));
        $secret = 'sha1='.hash_hmac('sha1', 'hello', config('auto-deploy.secret'));
        $server = [
            'HTTP_User-Agent' => 'GitHub-Hookshot 12345',
            'REMOTE_ADDR' => '192.30.252.0',
            'HTTP_X-Hub-Signature' => $secret,
            'HTTP_X-GitHub-Event' => 'push',
            'HTTP_X-GihHub-Delivery' => '21EC2020-3AEA-4069-A2DD-08002B30309D',
        ];
        $uri = $this->app['url']->route('autodeployroute', []);
        $uri = str_replace('http', 'https', $uri);
        $response = $this->call('POST', $uri, [], [], [], $server, 'hello');
        $this->assertResponseOk();
        $controller = new ReflectionObject($this->app['Morphatic\AutoDeploy\Controllers\DeployController']);
        $origin = $controller->getProperty('origin');
        $origin->setAccessible(true);
        $origin = $origin->getValue($this->app['Morphatic\AutoDeploy\Controllers\DeployController']);
        $this->assertEquals('Morphatic\AutoDeploy\Origins\Github', get_class($origin));
    }
}
