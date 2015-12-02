<?php

use Orchestra\Testbench\TestCase;

class DeployControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Morphatic\AutoDeploy\AutoDeployServiceProvider'];
    }

    public function testTrue()
    {
        $this->assertTrue(true);
    }
}
