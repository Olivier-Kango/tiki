<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TokensClearCommand extends Command
{
    protected static $defaultDescription = 'Remove expired tokens';
    protected function configure()
    {
        $this
            ->setName('tokens:clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        require_once 'lib/auth/tokens.php';

        $tokenlib = \AuthTokens::build($prefs);
        $affectedRows = $tokenlib->deleteExpired();

        $output->writeln(tr('%0 tokens deleted.', $affectedRows->numrows), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('<comment>' . tr('Expired tokens were removed.') . '</comment>');
        return Command::SUCCESS;
    }
}
