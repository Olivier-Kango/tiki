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
use Symfony\Component\Mime\Email;
use Tiki\Installer\Installer;

#[AsCommand(
    name: 'mail-queue:delete-unsupported',
    description: 'Delete queued email messages that cannot be processed by the current mailer',
)]
class MailQueueDeleteUnsupportedCommand extends Command
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
            $output->writeln('<error>Cannot remove unsupported queued email messages because the mail queue table does not exist.</error>');
            return Command::FAILURE;
        }

        $entries = $installer->fetchAll('SELECT messageId, message FROM tiki_mail_queue');
        if ($entries === false) {
            $output->writeln('<error>Unable to read the mail queue.</error>');
            return Command::FAILURE;
        }

        $unsupportedIds = [];
        foreach ($entries as $entry) {
            $previousErrorReporting = error_reporting(0);
            try {
                $mail = unserialize($entry['message']);
            } finally {
                error_reporting($previousErrorReporting);
            }

            if (! $mail instanceof Email) {
                $unsupportedIds[] = $entry['messageId'];
            }
        }

        foreach ($unsupportedIds as $id) {
            if ($installer->query('DELETE FROM tiki_mail_queue WHERE messageId = ?', [$id]) === false) {
                $output->writeln('<error>Unable to remove unsupported mail queue entries.</error>');
                return Command::FAILURE;
            }
        }

        $deleted = count($unsupportedIds);
        if ($deleted === 0) {
            $output->writeln('<info>No unsupported queued email messages found.</info>');
        } else {
            error_log(sprintf( // @phpstan-ignore disallowedFunctions.errorLog (logs removed mail queue entries for traceability)
                'Removed %d unsupported mail queue entries. These were serialized in old Laminas/Zend format.',
                $deleted
            ));
            $output->writeln(sprintf(
                '<info>Removed %d unsupported mail queue entries in the old Laminas/Zend format. Their queued email data was permanently deleted.</info>',
                $deleted
            ));
        }

        return Command::SUCCESS;
    }
}
