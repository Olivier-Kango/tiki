<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AdminIndexRebuildCommand extends Command
{
    protected static $defaultDescription = 'Fully rebuild the preferences index';
    protected function configure()
    {
        $this
            ->setName('preferences:rebuild-index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \TikiLib::lib('prefs')->rebuildIndex();
        $output->writeln('Preferences index was rebuilt successfully.');
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
