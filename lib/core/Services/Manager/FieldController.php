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
 * Class Services_Manager_FieldController
 */
class Services_Manager_FieldController
{
    use Services_Manager_Trait;

    public function action_create($input)
    {
        global $user;

        $itemId = $input->itemId->int();
        $fieldId = $input->fieldId->int();

        if (empty($itemId) || empty($fieldId)) {
            throw new Services_Exception(tr("Missing itemId or fieldId."));
        }

        $field = TikiLib::lib('trk')->get_field_info($fieldId);

        if (! $field) {
            throw new Services_Exception(tr("Field not found: %0", $fieldId));
        }

        $handler = TikiLib::lib('trk')->get_field_handler($field);
        $type = $handler->getOption('newInstanceType');
        $conn_host = $handler->getOption('newInstanceHost');
        $conn_port = $handler->getOption('newInstancePort');
        $conn_user = $handler->getOption('newInstanceUser');
        $conn_pass = $handler->getOption('newInstancePass');
        $name = 'Item ' . $itemId . ' Field ' . $fieldId . ' Instance';
        $contact = TikiLib::lib('user')->get_user_email($user);
        $uniqid = uniqid();
        $weburl = str_replace('{slug}', $uniqid, $handler->getOption('newInstanceTemplateUrl'));
        $webroot = str_replace('{slug}', $uniqid, $handler->getOption('newInstanceWebroot'));
        $tempdir = str_replace('{slug}', $uniqid, $handler->getOption('newInstanceTempdir'));
        $backup_user = $handler->getOption('newInstanceBackupUser');
        $backup_group = $handler->getOption('newInstanceBackupGroup');
        $backup_perms = $handler->getOption('newInstanceBackupPerms');
        $branch = $input->version->text();
        $db_host = $handler->getOption('newInstanceDbHost');
        $db_user = $handler->getOption('newInstanceDbUser');
        $db_pass = $handler->getOption('newInstanceDbPass');
        $db_prefix = $handler->getOption('newInstanceDbPrefix');

        if ($db_prefix) {
            $db_prefix .= 'item'.$itemId.'field'.$fieldId;
        }

        // TODO: see if some other validation is needed (note that most of the valiation is in the actual create command and that error output is already passed to the user)
        foreach (['type'] as $param) {
            if (empty($$param)) {
                throw new Services_Exception(tr('Missing required field parameter: %0', $param));
            }
        }

        $cmd = new TikiManager\Command\CreateInstanceCommand();
        $input = new ArrayInput([
            'command' => $cmd->getName(),
            '--type' => $type,
            '--host' => $conn_host,
            '--port' => $conn_port,
            '--user' => $conn_user,
            '--pass' => $conn_pass,
            '--url' => $weburl,
            '--name' => $name,
            '--email' => $contact,
            '--webroot' => $webroot,
            '--tempdir' => $tempdir,
            '--branch' => $branch,
            '--backup-user' => $backup_user,
            '--backup-group' => $backup_group,
            '--backup-permission' => octdec($backup_perms),
            '--db-host' => $db_host,
            '--db-user' => $db_user,
            '--db-pass' => $db_pass,
            '--db-prefix' => $db_prefix,
        ]);
        $this->runCommand($cmd, $input);
        return [
            'title' => tr('Tiki Manager Create Instance'),
            'info' => $this->manager_output->fetch(),
        ];
    }

    public function loadEnv()
    {
        $this->loadManagerEnv();
        $this->setManagerOutput();
    }
}
