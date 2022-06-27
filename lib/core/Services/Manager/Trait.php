<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Package\ComposerManager;

trait Services_Manager_Trait
{
    protected $manager_output;

    public function setUp()
    {
        Services_Exception_Disabled::check('feature_tiki_manager');

        // TODO: add own set of permissions
        $perms = Perms::get();
        if (! $perms->admin) {
            throw new Services_Exception_Denied();
        }

        $this->ensureInstalled();
        $this->loadEnv();
    }

    protected function ensureInstalled()
    {
        if (! class_exists('TikiManager\Config\Environment')) {
            throw new Services_Exception_NotAvailable(tr('Tiki Manager not found. Please check if it is installed from Admin->Packages.'));
        }
    }

    abstract public function loadEnv();

    /**
     * Loads Tiki Manager config environment while setting up Tiki-related config.
     * This includes:
     * - use composer.phar already included with Tiki
     * - setup storage-based data paths, so updates and reinstalls don't loose data or backups
     * @throws \TikiManager\Config\Exception\ConfigurationErrorException
     */
    protected function loadManagerEnv($isWeb = true)
    {
        global $tikipath;

        $storage_path = $tikipath.'storage/tiki-manager';
        if (!is_dir($storage_path)) {
            if (!is_writable(dirname($storage_path))) {
                throw new TikiManager\Config\Exception\ConfigurationErrorException(tr('Unable to create data storage directory for Tiki Manager. Check if storage directory is writable by running permission fix.'));
            }
            mkdir($storage_path, 0777, true);
        }

        if (isset($_SERVER['SYMFONY_DOTENV_VARS']) || isset($_ENV['SYMFONY_DOTENV_VARS'])) {
            // in case loadEnv is called multiple times, we need to reset server/env .env variables
            // (e.g. when calling manager commands from within console command of another manager command)
            $loadedVars = explode(',', $_SERVER['SYMFONY_DOTENV_VARS'] ?? $_ENV['SYMFONY_DOTENV_VARS'] ?? '');
            foreach ($loadedVars as $name) {
                unset($_ENV[$name]);
                unset($_SERVER[$name]);
            }
            unset($_ENV['SYMFONY_DOTENV_VARS']);
            unset($_SERVER['SYMFONY_DOTENV_VARS']);
        }

        $_ENV['BACKUP_FOLDER'] = $storage_path.'/backup';
        $_ENV['ARCHIVE_FOLDER'] = $storage_path.'/backup/archive';
        $_ENV['TRIM_LOGS'] = $storage_path.'/logs';
        $_ENV['TRIM_DATA'] = $storage_path.'/data';
        $_ENV['TRIM_SRC_FOLDER'] = $storage_path.'/data/tiki_src';

        $composerManager = new ComposerManager($tikipath);
        $composerPath = $composerManager->composerPath();

        $tm_env = TikiManager\Config\Environment::getInstance();
        if (file_exists($composerPath)) {
            $tm_env->setComposerPath($composerPath);
        }
        // suppress default io output while loading Tiki Manager env (errors are thrown as exceptions anyway)
        // we switch to manager_output buffered output after initialization to be able to get command output
        $tm_env->setIO(null, new NullOutput());
        $tm_env->load();

        $_ENV['SSH_CONFIG'] = $_ENV['TRIM_ROOT'].'/data/ssh_config';

        if ($isWeb) {
            $_ENV['RUN_THROUGH_TIKI_WEB'] = $isWeb;
        }
    }

    /**
     * Sets up a new BufferedOutput to be used by Tiki Manager code instead of the default ConsoleOutput
     */
    protected function setManagerOutput()
    {
        $this->manager_output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $formatter = TikiManager\Config\App::get('ConsoleHtmlFormatter');
        $this->manager_output->setFormatter($formatter);

        TikiManager\Config\Environment::getInstance()->setIo(null, $this->manager_output);
    }

    protected function runCommand($cmd, $input = null)
    {
        $cwd = getcwd();
        if (! $input) {
            $input = new ArrayInput([
                'command' => $cmd->getName(),
            ]);
        }
        $input->setInteractive(false);
        $app = new Application();
        $app->add($cmd);
        $app->setAutoExit(false);
        $app->run($input, $this->manager_output);
        // some TM commands might change current working dir
        chdir($cwd);
        $output = $this->manager_output->fetch();
        if (preg_match('/git config.*?safe.directory\s*([^\s]+)/', $output, $m)) {
            $this->manager_output->writeln(tr('Error encountered when trying to run git commands. You can fix by manually executing this command on the server with proper permissions:'));
            $this->manager_output->writeln("git config --system --add safe.directory $m[1]");
        } else {
            $this->manager_output->write($output);
        }
    }

    protected function getCommandHelpTexts($cmd)
    {
        $help = [];
        $definition = $cmd->getDefinition();
        foreach ($definition->getOptions() as $option) {
            $help[$option->getName()] = $option->getDescription();
        }
        return $help;
    }

    protected function createVirtualminTikiInstance($source, $remote_user, $domain, $email, $name, $branch)
    {
        $sources_table = TikiDb::get()->table('tiki_source_auth', false);
        $record = $sources_table->fetchFullRow([
            'identifier' => $source,
        ]);
        if (! $record) {
            $info = parse_url($source);
            $record = $sources_table->fetchFullRow([
                'scheme' => $info['scheme'],
                'domain' => $info['host'],
                'path' => $info['path'],
            ]);
        }
        if (! $record) {
            throw new Services_Exception(tr("Invalid or missing source specified: %0", $source));
        }
        $source_url = "{$record['scheme']}://{$record['domain']}:10000/virtual-server/remote.cgi?json=1&multiline&";

        $params = [
            'program' => 'create-domain',
            'domain' => $domain,
            'user' => $remote_user,
            'group' => $remote_user,
            'pass' => TikiLib::genPass(),
            'mysql-pass' => TikiLib::genPass(),
            'default-features' => '',
            'email' => $email,
        ];
        $client = TikiLib::lib('tiki')->get_http_client($source_url.http_build_query($params, '', '&'), [
            'timeout' => 300,
        ]);
        $response = $client->send();
        $response = json_decode($response->getBody(), true);
        if (! empty($response['error'])) {
            throw new Services_Exception($response['error']);
        } elseif (empty($response['output'])) {
            throw new Services_Exception(tr('Unrecognized response: %0', print_r($response, 1)));
        } else {
            $output = $response['output'];

            $ftp = new TikiManager\Libs\Host\FTP($params['domain'], $params['user'], $params['pass'], 21);
            $ftp->createDirectory('.ssh');
            $ftp->sendFile($_ENV['SSH_PUBLIC_KEY'], '.ssh/authorized_keys');
            $ftp->chmod(0600, '.ssh/authorized_keys');

            $client = TikiLib::lib('tiki')->get_http_client($source_url."program=list-domains&domain=".urlencode($domain), [
                'timeout' => 300,
            ]);
            $response = $client->send();
            $response = json_decode($response->getBody(), true);
            $response = $response['data'][0]['values'];
            $webroot = $response['html_directory'][0];

            $temp_instance = new TikiManager\Application\Instance;
            $temp_access = new TikiManager\Access\Local($temp_instance);
            if (stristr(PHP_OS, 'WIN')) {
                $discovery = new TikiManager\Application\Discovery\WindowsDiscovery($temp_instance, $temp_access, ['os' => 'WINDOWS']);
            } else {
                $discovery = new TikiManager\Application\Discovery\LinuxDiscovery($temp_instance, $temp_access, ['os' => 'LINUX']);
            }
            list($backup_user, $backup_group, $backup_perm) = $discovery->detectBackupPerm($_ENV['BACKUP_FOLDER']);

            $cmd = new TikiManager\Command\CreateInstanceCommand();
            $inputCommand = new ArrayInput([
                'command' => $cmd->getName(),
                "--type" => 'ssh',
                "--host" => $params['domain'],
                "--user" => $params['user'],
                "--url" => $response['url'][0],
                "--name" => $name,
                "--email" => $params['email'],
                "--webroot" => $webroot,
                "--tempdir" => '/home/' . $params['user'] . '/tmp/trim_temp', // using default /tmp/trim_temp dir on virtualmin server is risky as they might already been created by another user and not writable by the current user
                "--force" => '1',
                "--branch" => $branch,
                "--backup-user" => $backup_user,
                "--backup-group" => $backup_group,
                "--backup-permission" => '755',
                "--db-host" => 'localhost',
                "--db-user" => $params['user'],
                "--db-pass" => $params['mysql-pass'],
                "--db-name" => $params['user'],
            ]);
            $this->runCommand($cmd, $inputCommand);

            $output .= "\n\n" . $this->manager_output->fetch();

            return $output;
        }
    }
}
