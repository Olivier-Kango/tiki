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
    name: 'profile:export:rating-config',
    description: 'Export an advanced rating configuration'
)]
class RatingConfig extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Configuration ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('config');

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_RatingConfig::export($writer, $id);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Configuration not found: $id");
        }
        return Command::SUCCESS;
    }
}
