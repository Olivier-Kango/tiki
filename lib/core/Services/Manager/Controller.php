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

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class Services_Manager_Controller
 */
class Services_Manager_Controller
{
    use Services_Manager_Trait;

    public function action_index()
    {
        return [
            'title' => tr('Tiki Manager'),
            'instances' => TikiManager\Application\Instance::getInstances(true),
        ];
    }

    public function action_info()
    {
        $this->runCommand(new TikiManager\Command\ManagerInfoCommand());
        return [
            'title' => tr('Tiki Manager Info'),
            'info' => $this->manager_output->fetch(),
        ];
    }

    public function action_update($input)
    {
        $instanceId = $input->instanceId->int();
        if ($instance = TikiManager\Application\Instance::getInstance($instanceId)) {
            Scheduler_Manager::queueJob('Update instance '.$instanceId, 'ConsoleCommandTask', ['console_command' => 'manager:instance:update -i '.$instanceId]);
            Feedback::success(tr("Instance %0 scheduled to update in the background. You can check command output via <a href='tiki-admin_schedulers.php#contenttabs_admin_schedulers-3'>Scheduler logs</a>.", $instanceId));
        } else {
            Feedback::error(tr('Unknown instance'));
        }
        if ($input->modal->int()) {
            return Services_Utilities::closeModal();
        } else {
            return [
                'FORWARD' => [
                    'action' => 'index',
                ],
            ];
        }
    }

    public function action_fix($input)
    {
        $instanceId = $input->instanceId->int();
        if ($instance = TikiManager\Application\Instance::getInstance($instanceId)) {
            try {
                $instance->getApplication()->fixPermissions();
                Feedback::success(tr("Fixed permissions."));
            } catch (\Exception $e) {
                Feedback::error($e->getMessage());
            }
        } else {
            Feedback::error(tr('Unknown instance'));
        }
        $content = $this->manager_output->fetch();
        if ($content) {
            return [
                'override_action' => 'info',
                'title' => tr('Tiki Manager Instance Fix'),
                'info' => $content,
            ];
        } else {
            if ($input->modal->int()) {
                return Services_Utilities::closeModal();
            } else {
                return [
                    'FORWARD' => [
                        'action' => 'index',
                    ],
                ];
            }
        }
    }

    public function action_delete($input)
    {
        $cmd = new TikiManager\Command\DeleteInstanceCommand();
        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $input->instanceId->int(),
        ]);
        $this->runCommand($cmd, $input);
        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Delete Instance'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }

    public function action_watch($input)
    {
        $cmd = new TikiManager\Command\WatchInstanceCommand();
        $instances = TikiManager\Application\Instance::getInstances(true);
        $instance = TikiManager\Application\Instance::getInstance($input->instanceId->int());

        $IDs = [];

        foreach ($instances as $inst) {
            if ($inst->id != $input->instanceId->int()) {
               $IDs [] = $inst->id;
            }
        }

        $instanceIds = implode(',', $IDs);

        $input = new ArrayInput([
            'command' => $cmd->getName(),
                "--email" => $instance->contact,
                "--exclude" => $instanceIds,
        ]);

        if (empty($this->manager_output->fetch())) {
            try {
                $this->runCommand($cmd, $input);
                Feedback::success(tr("Successful Tiki Manager Watch Instance"));
            } catch (\Exception $e) {
                Feedback::error($e->getMessage());
            }
            return [
                'FORWARD' => [
                    'action' => 'index',
                ],
            ];
        }else{
            return [
                'override_action' => 'info',
                'title' => tr('Tiki Manager Watch Instance'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        }
    }

    public function action_access($input)
    {
        $cmd = new TikiManager\Command\AccessInstanceCommand();
        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $input->instanceId->int(),
        ]);
        $this->runCommand($cmd, $input);

        return [
            'override_action' => 'access',
            'title' => tr('Tiki Manager Access Command'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }

    public function loadEnv()
    {
        global $prefs, $user, $base_url, $tikipath;

        $this->loadManagerEnv();
        $this->setManagerOutput();

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
            $instance->tempdir = $_ENV['TEMP_FOLDER'];
            $instance->backup_user = $access->user;
            $instance->backup_group = @posix_getgrgid(posix_getegid())['name'];
            $instance->backup_perm = 0770;
            $instance->save();
            $access->save();

            $instance->detectPHP();
            $instance->findApplication();
        }
    }
}
