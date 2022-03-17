<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Console\Output\BufferedOutput;
use Tiki\Package\ComposerManager;

class Services_Manager_Utilities
{
    /**
     * Loads Tiki Manager config environment while setting up Tiki-related config.
     * This includes:
     * - use composer.phar already included with Tiki
     * - setup storage-based data paths, so updates and reinstalls don't loose data or backups
     * @throws \TikiManager\Config\Exception\ConfigurationErrorException
     */
    public static function loadManagerEnv()
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
        $tm_env->load();

        $_ENV['SSH_CONFIG'] = $_ENV['TRIM_ROOT'].'/data/ssh_config';
    }

    /**
     * Sets up a new BufferedOutput to be used by Tiki Manager code instead of the default ConsoleOutput
     */
    public static function getManagerOutput()
    {
        $manager_output = new BufferedOutput();
        $formatter = TikiManager\Config\App::get('ConsoleHtmlFormatter');
        $manager_output->setFormatter($formatter);

        TikiManager\Config\Environment::getInstance()->setIo(null, $manager_output);

        return $manager_output;
    }
}
