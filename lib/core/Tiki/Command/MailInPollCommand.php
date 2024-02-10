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

error_reporting(E_ALL);
use Tiki\MailIn;
use TikiLib;

#[AsCommand(
    name: 'mail-in:poll',
    description: 'Read the mail-in messages'
)]
class MailInPollCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mailinlib = TikiLib::lib('mailin');
        $accs = $mailinlib->list_active_mailin_accounts(0, -1, 'account_desc', '');

        // foreach account
        foreach ($accs['data'] as $acc) {
            if (empty($acc['account'])) {
                continue;
            }

            $account = MailIn\Account::fromDb($acc);
            $account->check();
        }
        return Command::SUCCESS;
    }
}
