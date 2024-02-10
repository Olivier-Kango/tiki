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
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:tracker-field',
    description: 'Export a tracker field definition'
)]
class TrackerField extends ObjectWriter
{
    protected function configure()
    {
        $this
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
