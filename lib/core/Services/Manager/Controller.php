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
use TikiManager\Application\Instance;
use TikiManager\Application\Tiki\Versions\Fetcher\YamlFetcher;
use TikiManager\Application\Tiki\Versions\TikiRequirementsHelper;

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
                Feedback::success(tr('Successful Tiki Manager Watch Instance, Notifications will be sent to <b>%0</b>', htmlspecialchars($instance->contact)));
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
            'title' => tr('Tiki Manager Access Command'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }


    public function action_detect($input)
    {
        $cmd = new TikiManager\Command\DetectInstanceCommand();
        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $input->instanceId->int(),
        ]);
        $this->runCommand($cmd, $input);
        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Detect Instance'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }

    public function action_create($input)
    {
        $cmd = new TikiManager\Command\CreateInstanceCommand();

        if ($input->create->text()){
            $inputCommand = new ArrayInput([
                'command' => $cmd->getName(),
                "--type" => $input->type->text(),
                "--host" => $input->host->text(),
                "--port" => $input->port->text(),
                "--user" => $input->user->text(),
                "--pass" => $input->pass->text(),
                "--url" => $input->url->text(),
                "--name" => $input->name->text(),
                "--email" => $input->email->text(),
                "--webroot" => $input->webroot->text(),
                "--tempdir" => $input->tempdir->text(),
                "--branch" => $input->branch->text(),
                "--backup-user" => $input->backup_user->text(),
                "--backup-group" => $input->backup_group->text(),
                "--backup-permission" => $input->backup_permission->text(),
                "--db-host" => $input->db_host->text(),
                "--db-user" => $input->db_user->text(),
                "--db-pass" => $input->db_pass->text(),
                "--db-prefix" => $input->db_prefix->text(),
                "--db-name" => $input->db_name->text(),
            ]);

            $this->runCommand($cmd, $inputCommand);

            return [
                'title' => tr('Create New Instance Result'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        } else {

            /** For form initialization */
            $inputValues = [
                'types' => ['local', 'ftp', 'ssh'],
                'selected_type' => 'local',
                'host' => '',
                'port' => '',
                'user' => '',
                'pass' => '',
                'url' => '',
                'name' => "",
                'email' => '',
                'webroot' => '',
                'branches' => $this->getTikiBranches(),
                'selected_branch' => "21.x",
                'temp_dir' => '/tmp/trim_temp',
                'backup_user' => 'www-data',
                'backup_group' => 'www-data',
                'backup_permission' => '',
                'db_host' => '',
                'db_user' => '',
                'db_pass' => '',
                'db_prefix' => '',
                'db_name' => ''
            ];

            return [
                'title' => tr('Create New Instance'),
                'info' => '',
                'refresh' => true,
                'inputValues' => $inputValues,
                'help' => $this->getCommandHelpTexts($cmd),
                'sshPublicKey' => $_ENV['SSH_PUBLIC_KEY'],
            ];
        }
    }

    public function action_edit($input)
    {
        $cmd = new TikiManager\Command\EditInstanceCommand();

        if ($input->edit->text()){
            $inputCommand = new ArrayInput([
                'command' => $cmd->getName(),
                '-i' => $input->instance->int(),
                "--url" => $input->url->text(),
                "--name" => $input->name->text(),
                "--email" => $input->email->text(),
                "--webroot" => $input->webroot->text(),
                "--tempdir" => $input->tempdir->text(),
                "--backup-user" => $input->backup_user->text(),
                "--backup-group" => $input->backup_group->text(),
                "--backup-permission" => $input->backup_permission->text(),
            ]);

            $this->runCommand($cmd, $inputCommand);

            return [
                'title' => tr('Edit Instance Result'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        } else {

            $instaceId = $input->instanceId->int();
            $instance = Instance::getInstance($instaceId);

            if ($instance) {
                /** For form initialization */
                $inputValues = [
                    'instance' => $instaceId,
                    'url' => $instance->weburl,
                    'name' => $instance->name,
                    'email' => $instance->contact,
                    'webroot' => $instance->webroot,
                    'temp_dir' => $instance->tempdir,
                    'backup_user' => $instance->getProp('backup_user'),
                    'backup_group' => $instance->getProp('backup_group'),
                    'backup_permission' => decoct($instance->getProp('backup_perm')),
                ];

                return [
                    'title' => tr('Edit instance') . " " . $instance->backup_user,
                    'info' => '',
                    'refresh' => true,
                    'inputValues' => $inputValues,
                    'help' => $this->getCommandHelpTexts($cmd),
                ];
            } else {
                return [
                    'title' => tr('Edit instance (Instance not found)'),
                    'info' => "No Tiki instances available to edit",
                    'refresh' => true,
                ];
            }
            
        }
    }


    public function action_test_send_email($input)
    {
        $cmd = new TikiManager\Command\ManagerTestSendEmailCommand();

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $inputCommand = new ArrayInput([
                'command' => $cmd->getName(),
                "to" => $input->email->text(),
            ]);

            $this->runCommand($cmd, $inputCommand);

            return [
                'title' => tr('Test Send Email Result'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        } else {

            $inputValues = [
                'email' => ""
            ];

            return [
                'title' => tr('Test send email'),
                'info' => '',
                'refresh' => true,
                'inputValues' => $inputValues
            ];
            
        }
    }    

    public function action_virtualmin_create($input)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $source = $input->source->text();
            $domain = $input->domain->text();
            if (preg_match('/^([^\.]*)\./', $domain, $m)) {
                $remote_user = $m[1];
            } else {
                $remote_user = $domain;
            }
            $email = $input->email->text();
            $name = $input->name->text();
            $branch = $input->branch->text();
            $php_version = $input->php_version->text();

            try {
                $output = $this->createVirtualminTikiInstance($source, $remote_user, $domain, $email, $name, $branch, $php_version);
                return [
                    'title' => tr('Create Virtualmin Instance Result'),
                    'override_action' => 'info',
                    'info' => $output,
                    'refresh' => true,
                ];
            } catch (Services_Exception $e) {
                Feedback::error($e->getMessage());
            }
        }

        $cmd = new TikiManager\Command\CreateInstanceCommand();
        $sources_table = TikiDb::get()->table('tiki_source_auth', false);

        $sources = [];
        $records = $sources_table->fetchAll(['identifier', 'scheme', 'domain', 'path']);
        foreach ($records as $record) {
            $sources[$record['identifier']] = "{$record['identifier']}: {$record['scheme']}://{$record['domain']}{$record['path']}";
        }

        return [
            'title' => tr('Create New Virtualmin Instance'),
            'branches' => $this->getTikiBranches(),
            'help' => $this->getCommandHelpTexts($cmd),
            'input' => $input->asArray(),
            'sources' => $sources,
        ];
    }

    public function action_available_versions($input)
    {
        $result = [
            'php_versions' => [],
            'available_branches' => $this->getTikiBranches(),
        ];

        $source = $input->source->text();
        $php_version = $input->php_version->text();
        $selected_php_version = null;

        $params = [
            'program' => 'list-php-versions',
            'name-only' => '',
        ];
        $response = $this->virtualminRemoteCommand($source, $params);
        foreach ($response['data'] as $row) {
            $result['php_versions'][] = $row['name'];
            if ($row['name'] == $php_version) {
                $selected_php_version = $php_version;
            }
        }

        if ($selected_php_version) {
            $available_versions = [];
            $requirements = (new YamlFetcher())->getRequirements();
            foreach ($requirements as $requirement) {
                if ($requirement->getPhpVersion()->isValidVersion($selected_php_version)) {
                    $available_versions[] = $requirement->getVersion();
                }
            }
            $result['available_branches'] = array_values(array_filter($result['available_branches'], function($branch) use ($available_versions) {
                if ($branch == 'master') {
                    return true;
                }
                foreach ($available_versions as $version) {
                    if (substr($branch, 0, strlen($version)) == $version || preg_match("/$version\.\d+/", $branch)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        return $result;
    }

    public function action_clone($input)
    {
        if ($input->clone->text()){
            $cmd = new TikiManager\Command\CloneInstanceCommand();
            $inputCommand = new ArrayInput(array_merge([
                'command' => $cmd->getName(),
            ], $input->options->asArray()));

            $this->runCommand($cmd, $inputCommand);

            return [
                'title' => tr('Clone Tiki Instance Result'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        } else {
            $instances = TikiManager\Application\Instance::getInstances(true);

            $cmd = new TikiManager\Command\CloneInstanceCommand();
            $definition = $cmd->getDefinition();

            $options = [];
            foreach ($definition->getOptions() as $option) {
                switch ($option->getName()) {
                    case 'source':
                    case 'target':
                        $type = 'select';
                        $values = [];
                        foreach ($instances as $i) {
                            $values[$i->id] = $i->name;
                        }
                        $selected = $input->instanceId->int() ? $input->instanceId->int() : '';
                        break;
                    case 'branch':
                        $type = 'select';
                        $values = array_combine($this->getTikiBranches(), $this->getTikiBranches());
                        $selected = 'master';
                        break;
                    default:
                        if ($option->acceptValue()) {
                            $type = 'text';
                        } else {
                            $type = 'checkbox';
                        }
                        $values = [];
                        $selected = $option->getDefault();
                }

                $options[] = [
                    'name' => $option->getName(),
                    'label' => ucwords(str_replace('-', ' ', $option->getName())),
                    'type' => $type,
                    'values' => $values,
                    'selected' => $selected,
                    'help' => $option->getDescription(),
                    'is_array' => $option->isArray(),
                ];
            }

            return [
                'title' => tr('Clone Tiki Instance'),
                'options' => $options,
            ];
        }
    }

    public function action_console($input)
    {
        $instanceId = $input->instanceId->int();
        if (TikiManager\Application\Instance::getInstance($instanceId)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $cmd = new TikiManager\Command\ConsoleInstanceCommand();
                $inputCmd = new ArrayInput([
                    'command' => $cmd->getName(),
                    '-i' => $instanceId,
                    '-c' => $input->command->text(),
                ]);
                try {
                    $this->runCommand($cmd, $inputCmd);
                } catch (\Exception $e) {
                    Feedback::error($e->getMessage());
                }
                return [
                    'override_action' => 'info',
                    'title' => tr('Tiki Manager Console Command'),
                    'info' => $this->manager_output->fetch(),
                    'refresh' => true,
                ];
        
            } else {    
                return [
                    'title' => tr('Tiki Manager Console Command'),
                    'info' => '',
                    'instanceId' => $input->instanceId->int()
                ];
            }
        } else {
            Feedback::error(tr('Unknown instance'));
            return [
                'FORWARD' => [
                    'action' => 'index',
                ],
            ];
        }
        
    }


    public function action_check($input)
    {
        $cmd = new TikiManager\Command\CheckInstanceCommand();
        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $input->instanceId->int()
        ]);
        $this->runCommand($cmd, $input);
        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Check Instance'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }
    
    
    public function action_requirements($input)
    {
        $this->runCommand(new TikiManager\Command\CheckRequirementsCommand());
        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Check Requirements'),
            'info' => $this->manager_output->fetch()
        ];
    }

    public function loadEnv()
    {
        global $prefs, $user, $base_url, $tikipath;

        $this->loadManagerEnv();
        $this->setManagerOutput();

        // check current instance exist
        $existing = TikiManager\Application\Instance::getInstances(true);
        $found = false;
        foreach ($existing as $instance) {
            if ($instance->weburl == $base_url && $instance->type == 'local') {
                $found = true;
                break;
            }
        }

        // and import it if not
        if (! $found) {
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

    public function getTikiBranches()
    {
        return Services_Manager_Utilities::getAvailableTikiVersions();
    }
    
    public function action_apply($input)
    {
        $instanceId = $input->instanceId->int();
        if (TikiManager\Application\Instance::getInstance($instanceId)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $cmd = new TikiManager\Command\ApplyProfileCommand();
                $inputCmd = new ArrayInput([
                    'command' => $cmd->getName(),
                    '-i' => $instanceId,
                    '-p' => $input->profile->text(),
                    '-r' => $input->repository->text(),
                ]);
                try {
                    $this->runCommand($cmd, $inputCmd);
                } catch (\Exception $e) {
                    Feedback::error($e->getMessage());
                }
                return [
                    'title' => tr('Tiki Manager Apply Profile'),
                    'info' => $this->manager_output->fetch(),
                    'refresh' => true,
                ];
        
            } else {    
                return [
                    'title' => tr('Apply a profile'),
                    'info' => '',
                    'instanceId' => $input->instanceId->int()
                ];
            }
        } else {
            Feedback::error(tr('Unknown instance'));
            return [
                'FORWARD' => [
                    'action' => 'index',
                ],
            ];
        }
    }

    public function action_verify($input)
    {
        $cmd = new TikiManager\Command\CheckInstanceCommand();

        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $input->instanceId->int()
        ]);

        $this->runCommand($cmd, $input);

        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Verify Instance'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }
    
    public function action_maintenance($input)
    {
        $cmd = new TikiManager\Command\MaintenanceInstanceCommand();
        $instanceId = $input->instanceId->int();
        $mode = $input->mode->text();

        $inputCommand = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $instanceId,
            'status' => $mode
        ]);

        $this->runCommand($cmd, $inputCommand);

        return [
            'override_action' => 'info',
            'title' => tr('Tiki Manager Instance Maintenance'),
            'info' => $this->manager_output->fetch(),
            'refresh' => true,
        ];
    }
    
    public function action_tiki_versions($input)
    {
        $cmd = new TikiManager\Command\TikiVersionCommand();

        if ($input->filter->text()){
            $inputCommand = new ArrayInput([
                'command' => $cmd->getName(),
                "--vcs" => $input->vcs->text(),
            ]);

            $this->runCommand($cmd, $inputCommand);

            return [
                'title' => tr('Tiki Versions'),
                'info' => $this->manager_output->fetch(),
                'refresh' => true,
            ];
        } else {
            /** Form initialization */
            $inputValues = [
                'vcs' => ['git', 'svn', 'src'],
                'selected_vcs' => 'git'
            ];

            return [
                'title' => tr('Tiki Versions'),
                'info' => '',
                'refresh' => true,
                'inputValues' => $inputValues,
                'help' => $this->getCommandHelpTexts($cmd)
            ];
            
        }
    }
}
