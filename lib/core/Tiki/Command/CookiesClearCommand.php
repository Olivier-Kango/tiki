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

#[AsCommand(
    name: 'users:remove-cookies',
    description: 'Remove expired cookies'
)]
class CookiesClearCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userlib = \TikiLib::lib('user');
        $affectedRows = $userlib->deleteExpiredCookies();

        $output->writeln(tr('%0 cookies deleted.', $affectedRows->numrows), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('<comment>' . tr('Expired cookies were removed.') . '</comment>');
        return Command::SUCCESS;
    }
}
