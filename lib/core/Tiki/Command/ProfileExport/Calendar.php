<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:calendar',
    description: 'Export a calendar'
)]
class Calendar extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all calendars'
            )
            ->addArgument(
                'calendar',
                InputArgument::OPTIONAL,
                'Calendar ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $calendarId = $input->getArgument('calendar');
        $all = $input->getOption('all');

        if (! $all && empty($calendarId)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "calendar" or "--all" option)') . '</error>');
            return (int) false;
        }

        $writer = $this->getProfileWriter($input);
        if (\Tiki_Profile_InstallHandler_Calendar::export($writer, $calendarId, $all)) {
            $writer->save();
        } else {
            $output->writeln("<error>Calendar not found: $calendarId</error>");
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
