<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class Services_Manager_Controller
 */
class Services_Manager_Controller
{
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

    public function action_index()
    {
        return [
            'title' => tr('Tiki Manager'),
            'instances' => TikiManager\Application\Instance::getInstances(true),
        ];
    }

    public function action_info()
    {
        $info = $this->runCommand(new TikiManager\Command\ManagerInfoCommand());
        return [
            'title' => tr('Tiki Manager Info'),
            'info' => $info,
        ];
    }

    protected function ensureInstalled()
    {
        if (! class_exists('TikiManager\Config\Environment')) {
            throw new Services_Exception_NotAvailable(tr('Tiki Manager not found. Please check if it is installed from Admin->Packages.'));
        }
    }

    protected function loadEnv()
    {
        global $prefs, $user, $base_url, $tikipath;

        TikiManager\Config\Environment::getInstance()->load();

        if (! TikiManager\Application\Instance::getInstances(true)) {
            // import current instance
            $instance = new TikiManager\Application\Instance;
            $instance->type = 'local';
            $access = $instance->getBestAccess();
            $discovery = $instance->getDiscovery();

            if ($type == 'local') {
                $access->host = 'localhost';
                $access->user = $discovery->detectUser();
            }

            $instance->name = $prefs['browsertitle'];
            $instance->contact = TikiLib::lib('user')->get_user_email($user);
            $instance->weburl = $base_url;
            $instance->webroot = rtrim($tikipath, '/');
            $instance->tempdir = rtrim($tikipath, '/').'/temp';
            $instance->backup_user = $access->user;
            $instance->backup_group = @posix_getgrgid(posix_getegid())['name'];
            $instance->backup_perm = octdec(0770);
            $instance->save();
            $access->save();

            $instance->detectPHP();
            $instance->findApplication();
        }
    }

    protected function runCommand($cmd, $input = null)
    {
        if (! $input) {
            $input = new ArrayInput([
                'command' => $cmd->getName(),
            ]);
        }
        $output = new BufferedOutput();
        $formatter = TikiManager\Config\App::get('ConsoleHtmlFormatter');
        $output->setFormatter($formatter);

        $app = new Application();
        $app->add($cmd);
        $app->setAutoExit(false);
        $app->run($input, $output);

        return $output->fetch();
    }
}
