<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Installer\Installer;

#[AsCommand(
    name: 'mail-queue:truncate',
    description: 'Delete all messages stored in the mail queue',
)]
class MailQueueTruncateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installer = Installer::getInstance();
        if (! $installer->tableExists('users_users')) {
            $output->writeln('<error>Database not found.</error>');
            return Command::FAILURE;
        }

        require_once('lib/tikilib.php');

        if (! $installer->tableExists('tiki_mail_queue')) {
            $output->writeln('<error>Cannot truncate the mail queue because the tiki_mail_queue table does not exist.</error>');
            return Command::FAILURE;
        }

        if (! $installer->query('TRUNCATE TABLE tiki_mail_queue')) {
            $output->writeln('<error>Unable to truncate the mail queue.</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Mail queue truncated. All queued email data was deleted.</info>');
        return Command::SUCCESS;
    }
}
