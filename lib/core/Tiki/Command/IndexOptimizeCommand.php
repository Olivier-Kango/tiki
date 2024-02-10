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
    name: 'index:optimize',
    description: 'Optimize the unified search index'
)]
class IndexOptimizeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $unifiedsearchlib = \TikiLib::lib('unifiedsearch');

        $output->writeln('Started optimizing index...');

        $unifiedsearchlib->getIndex('data-write')->optimize();

        $output->writeln('Optimizing index done');
        return Command::SUCCESS;
    }
}
