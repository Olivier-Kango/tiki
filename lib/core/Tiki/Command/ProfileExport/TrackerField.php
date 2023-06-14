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

class TrackerField extends ObjectWriter
{
    protected static $defaultDescription = 'Export a tracker field definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:tracker-field')
            ->addArgument(
                'tracker-field',
                InputArgument::REQUIRED,
                'Tracker field ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fieldId = $input->getArgument('tracker-field');

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_TrackerField::export($writer, $fieldId);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Tracker field not found: $fieldId");
        }
        return Command::SUCCESS;
    }
}
