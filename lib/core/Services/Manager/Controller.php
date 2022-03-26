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
        if ($input->mode->text() == 'bg') {
            Scheduler_Manager::queueJob('Update instance '.$instanceId, 'ConsoleCommandTask', ['console_command' => 'manager:instance:update -i '.$instanceId]);
            Feedback::success(tr("Instance %0 scheduled to update in the background. You can check command output via Scheduler logs.", $instanceId));
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
        $error = null;
        if ($instance = TikiManager\Application\Instance::getInstance($instanceId)) {
            $locked = (md5_file(TRIMPATH . '/scripts/maintenance.htaccess') == md5_file($instance->getWebPath('.htaccess')));

            try {
                if (! $locked) {
                    $locked = $instance->lock();
                }

                $instance->detectPHP();
                $app = $instance->getApplication();
                $app->performUpdate($instance);

                if ($locked) {
                    $instance->unlock();
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = tr('Unknown instance');
        }
        $content = $this->manager_output->fetch();
        if ($content) {
            if ($error) {
                $content .= "\n" . tr("Error: %0", $error);
            }
            return [
                'override_action' => 'info',
                'title' => tr('Tiki Manager Instance Update'),
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
