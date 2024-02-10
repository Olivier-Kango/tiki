<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

/**
 * Class PerformanceCheckCommand
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'performance:check',
    description: 'Check statistics of some performance related statistics'
)]
class PerformanceCheckCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $performanceStatsLib = TikiLib::lib('performancestats');

        $output->writeln('Top requests by average processing time:');
        $averageLoadTimeTable = new Table($output);
        $averageLoadTimeTable->setHeaders(['#', tr('URL'), tr('Average load time (seconds)')]);
        $column = 0;
        $average_time_requests = $performanceStatsLib->getRequestsBasedOnAverageRequestTime();

        while ($res = $average_time_requests->fetchRow()) {
            $averageLoadTimeTable->setRow($column, [++$column, $performanceStatsLib->simplifyURL($res['url']), $res['average_time_taken'] / 1000]);
        }

        $averageLoadTimeTable->render();

        $output->writeln("\nTop requests by maximum processing time:");
        $maximumLoadTimeTable = new Table($output);
        $maximumLoadTimeTable->setHeaders(['#', tr('URL'), tr('Maximum load time (seconds)')]);
        $column = 0;
        $maximumTimeRequests = $performanceStatsLib->getRequestsBasedOnMaximumProcessingTime();

        while ($res = $maximumTimeRequests->fetchRow()) {
            $maximumLoadTimeTable->setRow($column, [++$column, $performanceStatsLib->simplifyURL($res['url']), $res['maximum_time_taken'] / 1000]);
        }

        $maximumLoadTimeTable->render();
        return Command::SUCCESS;
    }
}
