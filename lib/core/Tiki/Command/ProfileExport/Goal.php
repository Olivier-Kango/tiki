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
    name: 'profile:export:goal',
    description: 'Export a goal'
)]
class Goal extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addArgument(
                'goal',
                InputArgument::REQUIRED,
                'Goal ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $goal = $input->getArgument('goal');

        $writer = $this->getProfileWriter($input);

        if (\Tiki_Profile_InstallHandler_Goal::export($writer, $goal)) {
            $writer->save();
        } else {
            $output->writeln("<error>Goal not found: $goal</error>");
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
