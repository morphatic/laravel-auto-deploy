<?php

namespace Morphatic\AutoDeploy\Commands;

use Illuminate\Console\Command;

class DeployInfoCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'deploy:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information necessary to set up a deployment webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('deploy:init', ['--show' => true]);
    }
}
