<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

class TrackerClearCommand extends Command
{
    protected static $defaultDescription = 'Clear all items from a tracker without warning or notifications. Use with care!';
    protected function configure()
    {
        $this
            ->setName('tracker:clear')
            ->addArgument(
                'trackerId',
                InputArgument::REQUIRED,
                'ID of tracker import-export format to use'
            )
            ->addOption(
                'confirm',
                null,
                InputOption::VALUE_NONE,
                'Required to confirm deletion of all items'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');

        $output->writeln('<info>Clearing tracker...</info>');

        $trackerId = $input->getArgument('trackerId');
        $tracker = \Tracker_Definition::get($trackerId);

        if (! $tracker) {
            throw new \Exception('Tracker Clear: Tracker not found');
        }

        $perms = \Perms::get('tracker', $trackerId);
        if (! $perms->admin_trackers) {
            throw new \Exception('Tracker Clear: Admin permission required');
        }

        $confirm = $input->getOption('confirm');

        $utilities = new \Services_Tracker_Utilities();
        if ($confirm) {
            $utilities->clearTracker($trackerId);
            $output->writeln('<info>Tracker clear done</info>');
            $logslib->add_action('tracker clear', 'system', 'system', 'tracker #' . $trackerId . ' cleared.');
        } else {
            $name = $tracker->getConfiguration('name');
            $output->writeln("<info>Use the --confirm option to proceed with the clear operation.</info>");
            $output->writeln("<info>  There is NO undo and no notifications will be sent.</info>");
            $output->writeln("<info>  All items in tracker #$trackerId \"$name\" will be deleted.</info>");
        }

        return Command::SUCCESS;
    }
}
