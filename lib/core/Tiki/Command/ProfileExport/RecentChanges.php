<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:recent-changes',
    description: 'List the recent changes in prevision of export'
)]
class RecentChanges extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'since',
                null,
                InputOption::VALUE_REQUIRED,
                'Date from which the actions should be read in the log, can either be a date or a relative time period'
            )
            ->addOption(
                'ignore',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Adds an object to the ignore list. Format: object_type:object_id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($since = $input->getOption('since')) {
            $since = strtotime($since);
        }

        $ignoreList = [];
        foreach ($input->getOption('ignore') as $object) {
            if (preg_match("/^(?P<type>\w+):(?P<object>.+)$/", $object, $parts)) {
                $ignoreList[] = $parts;
            }
        }

        $since = $since ?: 0;

        $logs = \TikiDb::get()->table('tiki_actionlog');
        $actions = $logs->fetchAll(
            [
                'timestamp' => 'lastModif',
                'action',
                'type' => 'objectType',
                'object',
                'detail' => 'comment',
            ],
            [
                'lastModif' => $logs->greaterThan($since),
            ],
            -1,
            -1,
            'lastModif_asc'
        );

        $queue = new \Tiki_Profile_Writer_Queue();
        foreach ($actions as $action) {
            $queue->add($action);
        }

        $writer = $this->getProfileWriter($input);

        if (count($ignoreList)) {
            foreach ($ignoreList as $entry) {
                $writer->addFake($entry['type'], $entry['object']);
            }

            $writer->save();
        }

        $queue->filterIncluded($writer);
        $queue->filterInstalled(new \Tiki_Profile_Writer_ProfileFinder());

        $output->writeln((string) $queue);
        return Command::SUCCESS;
    }
}
