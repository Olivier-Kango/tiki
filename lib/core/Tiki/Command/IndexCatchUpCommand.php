<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'index:catch-up',
    description: 'Catch-up on incremental indexing.'
)]
class IndexCatchUpCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'amount',
                InputArgument::OPTIONAL,
                'Amount of queue entries to catch-up on',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount = (int) $input->getArgument('amount');

        $unifiedsearchlib = \TikiLib::lib('unifiedsearch');

        try {
            $output->writeln('Started processing queue...');

            $result = $unifiedsearchlib->processUpdateQueue($amount);

            $count = $unifiedsearchlib->getQueueCount();

            $output->writeln('Processing completed. Amount remaining: ' . $count);
        } catch (\Exception $e) {
            $msg = tr('Search index could not be updated: %0', $e->getMessage());
            \Feedback::error($msg);
        }

        \Feedback::printToConsole($output);
        return Command::SUCCESS;
    }
}
