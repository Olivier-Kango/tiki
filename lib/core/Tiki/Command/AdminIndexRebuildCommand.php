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
    name: 'preferences:rebuild-index',
    description: 'Fully rebuild the preferences index'
)]
class AdminIndexRebuildCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \TikiLib::lib('prefs')->rebuildIndex();
        $output->writeln('Preferences index was rebuilt successfully.');
        return Command::SUCCESS;
    }
}
