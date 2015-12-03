<?php

namespace Morphatic\AutoDeploy\Controllers;

use Log;
use Mail;
use Monolog\Logger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use AdamBrett\ShellWrapper\Command\Builder as Command;
use AdamBrett\ShellWrapper\Command\CommandInterface;
use AdamBrett\ShellWrapper\Runners\Exec;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\SwiftMailerHandler;
use Morphatic\AutoDeploy\Origins\OriginInterface;

class DeployController extends Controller
{
    /**
     * The origin of the webhook request.
     *
     * @var Morphatic\AutoDeploy\Origins\OriginInterface
     */
    private $origin;

    /**
     * The URL of the repo to be cloned.
     *
     * @var string
     */
    private $repoUrl;

    /**
     * The absolute path of the directory on the server that contains the project.
     *
     * @var string
     */
    private $webroot;

    /**
     * The absolute path of the directory where the new deployment will be set up.
     *
     * @var string
     */
    private $installDir;

    /**
     * A log of the results of the entire deploy process.
     *
     * @var Monolog\Logger
     */
    private $log;

    /**
     * The commit ID for this commit.
     *
     * @var string
     */
    private $commitId;

    /**
     * The commit ID for this commit.
     *
     * @var AdamBrett\ShellWrapper\Runners\Exec
     */
    private $shell;

    /**
     * The result of this commit.
     *
     * @var array
     */
    private $result;

    /**
     * Create a new DeployController instance.
     *
     * @param Morphatic\AutoDeploy\Origins\OriginInterface $origin The origin of the webhook
     * @param AdamBrett\ShellWrapper\Runners\Exec          $exec   The shell command execution class
     */
    public function __construct(OriginInterface $origin, Exec $exec)
    {
        // set class variables related to the webhook origin
        $this->origin = $origin;
        $this->repoUrl = $this->origin->getRepoUrl();
        $this->commitId = $this->origin->getCommitId();

        // create an instance of the shell exec
        $this->shell = $exec;
    }

    /**
     * Handles incoming webhook requests.
     */
    public function index()
    {
        // get the parameters for the event we're handling
        $configKey = "auto-deploy.{$this->origin->name}.{$this->origin->event()}";
        $this->webroot = config("$configKey.webroot");
        $this->installDir = dirname($this->webroot).'/'.date('Y-m-d').'_'.$this->commitId;
        $steps = config("$configKey.steps");

        // set up logging to email
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        $msg = \Swift_Message::newInstance('Project Deployed')
                ->setFrom(["do_not_reply@$domain" => 'Laravel Auto-Deploy[$domain]'])
                ->setTo(config('auto-deploy.notify'))
                ->setBody('', 'text/html');
        $handler = new SwiftMailerHandler(Mail::getSwiftMailer(), $msg, Logger::NOTICE);
        $handler->setFormatter(new HtmlFormatter());
        $this->log = Log::getMonolog();
        $this->log->pushHandler($handler);

        // execute the configured steps
        $this->result = [
            'Commit_ID' => $this->commitId,
            'Timestamp' => date('r'),
            'output' => '',
        ];
        $whitelist = ['backupDatabase','pull','composer','npm','migrate','seed','deploy'];
        foreach ($steps as $step) {
            if (in_array($step, $whitelist) && !$this->{$step}()) {
                $this->log->error('Deploy failed.', $this->result);

                return;
            }
        }
        $this->log->notice('Deploy succeeded!', $this->result);
    }

    /**
     * Runs a shell command, logs, and handles the result.
     *
     * @param AdamBrett\ShellWrapper\CommandInterface $cmd The text of the command to be run
     *
     * @return bool True if the command was successful, false on error
     */
    private function ex(CommandInterface $cmd)
    {
        // try to run the command
        $this->shell->run($cmd);
        $output = $this->shell->getOutput();
        $returnValue = $this->shell->getReturnValue();

        // record the result
        $output = count($output) ? implode("\n", $output)."\n" : '';
        $this->result['output'] .= "$cmd\n$output";

        // return a boolean
        return 0 === $returnValue;
    }

    /**
     * Backup the database.
     *
     * @return bool True if the database was successfully backed up. False on error.
     */
    private function backupDatabase()
    {
        // get the name of the DB to backed up and the connection to use
        $dbdir = database_path();
        $dbconn = config('database.default');
        $dbname = config("database.connections.$dbconn.database");

        // make a directory for the backup file and switch into that directory
        $cmd = new Command('cd');
        $cmd->addParam($dbdir)
            ->addSubCommand('&&')
            ->addSubCommand('mkdir')
            ->addParam('backups');
        if ($this->ex($cmd)) {
            $cmd = new Command('cd');
            $cmd->addParam($dbdir.'/backups')
                ->addSubCommand('&&');
            switch ($dbconn) {
                case 'sqlite':
                    $cmd->addSubCommand('cp');
                    $cmd->addParam($dbname)
                        ->addParam('.');

                    return $this->ex($cmd);
                case 'mysql':
                    $cmd->addSubCommand('mysqldump');
                    $cmd->addParam($dbname)
                        ->addParam('>')
                        ->addParam("$dbname.sql");

                    return $this->ex($cmd);
                case 'pgsql':
                    $cmd->addSubCommand('pg_dump');
                    $cmd->addParam($dbname)
                        ->addParam('>')
                        ->addParam("$dbname.sql");

                    return $this->ex($cmd);
            }
        }

        return false;
    }

    /**
     * Create a new directory parallel to the webroot and clone the project into that directory.
     *
     * @return bool True if the clone is successful. False otherwise.
     */
    private function pull()
    {
        if (is_writable(dirname($this->installDir))) {
            $cmd = new Command('mkdir');
            $cmd->addFlag('p')
                ->addParam($this->installDir);
            if ($this->ex($cmd)) {
                $cmd = new Command('cd');
                $cmd->addParam($this->installDir)
                    ->addSubCommand('&&')
                    ->addSubCommand('git')
                    ->addSubCommand('clone')
                    ->addParam($this->repoUrl)
                    ->addParam('.');

                return $this->ex($cmd);
            }
        }

        return false;
    }

    /**
     * Update composer and run composer update.
     *
     * @return bool True if the update is successful. False otherwise.
     */
    private function composer()
    {
        $cmd = new Command('cd');
        $cmd->addParam($this->installDir)
            ->addSubCommand('&&')
            ->addSubCommand('composer')
            ->addParam('self-update')
            ->addSubCommand('&&')
            ->addSubCommand('composer')
            ->addParam('update')
            ->addArgument('no-interaction');

        return $this->ex($cmd);
    }

    /**
     * Run npm update.
     *
     * @return bool True if npm is successful. False otherwise.
     */
    private function npm()
    {
        $cmd = new Command('cd');
        $cmd->addParam($this->installDir)
            ->addSubCommand('&&')
            ->addSubCommand('npm')
            ->addParam('update');

        return $this->ex($cmd);
    }

    /**
     * Run any necessary database migrations.
     *
     * @return bool True if the migration is successful. False otherwise.
     */
    private function migrate()
    {
        $cmd = new Command('cd');
        $cmd->addParam($this->installDir)
            ->addSubCommand('&&')
            ->addSubCommand('php')
            ->addSubCommand('artisan')
            ->addParam('migrate')
            ->addArgument('force')
            ->addArgument('no-interaction');

        return $this->ex($cmd);
    }

    /**
     * Run any necessary database migrations.
     *
     * @return bool True if the migration is successful. False otherwise.
     */
    private function seed()
    {
        $cmd = new Command('cd');
        $cmd->addParam($this->installDir)
            ->addSubCommand('&&')
            ->addSubCommand('php')
            ->addSubCommand('artisan')
            ->addParam('db:seed');

        return $this->ex($cmd);
    }

    /**
     * Symlinks the new deploy directory to the webroot.
     *
     * @return bool True if the symlink is successful. False otherwise.
     */
    private function deploy()
    {
        $cmd = new Command('cd');
        $cmd->addParam(dirname($this->webroot))
            ->addSubCommand('&&')
            ->addSubCommand('ln')
            ->addFlag('fs')
            ->addParam($this->installDir)
            ->addParam($this->webroot);

        return $this->ex($cmd);
    }
}
