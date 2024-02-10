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
    name: 'profile:export:forum',
    description: 'Export a forum definition'
)]
class Forum extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all forums'
            )
            ->addArgument(
                'forum',
                InputArgument::OPTIONAL,
                'Forum ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $forumId = $input->getArgument('forum');
        $all = $input->getOption('all');

        if (! $all && empty($forumId)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "forum" or "--all" option)') . '</error>');
            return (int) false;
        }

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Forum::export($writer, $forumId, $all);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Forum not found: $forumId");
        }
        return Command::SUCCESS;
    }
}
