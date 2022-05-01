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
    protected function loadManagerEnv()
    {
        global $tikipath;

        $storage_path = $tikipath.'/storage/tiki-manager';
        if (!is_dir($storage_path)) {
            if (!is_writable(dirname($storage_path))) {
                throw new TikiManager\Config\Exception\ConfigurationErrorException(tr('Unable to create data storage directory for Tiki Manager. Check if storage directory is writable by running permission fix.'));
            }
            mkdir($storage_path, 0777, true);
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
    }
}
