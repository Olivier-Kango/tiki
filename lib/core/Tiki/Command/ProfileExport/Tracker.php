<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Tracker extends ObjectWriter
{
    protected static $defaultDescription = 'Export a tracker definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:tracker')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all trackers'
            )
            ->addArgument(
                'tracker',
                InputArgument::OPTIONAL,
                'Tracker ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $trackerId = $input->getArgument('tracker');
        $all = $input->getOption('all');

        if (! $all && empty($trackerId)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "tracker" or "--all" option)') . '</error>');
            return (int) false;
        }

        $ref = $input->getOption('reference');
        if ($ref && ! \Tiki_Profile::isValidReference($ref, true)) {
            $output->writeln('<error>The value provided for the parameter reference do not have the right format: ' . $ref . '</error>');
            return Command::INVALID;
        }

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Tracker::export($writer, $trackerId, $all);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Tracker not found: $trackerId");
        }
        return Command::SUCCESS;
    }
}
