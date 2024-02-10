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
    name: 'profile:export:wiki-page',
    description: 'Export a wiki page'
)]
class WikiPage extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all wiki pages'
            )
            ->addArgument(
                'page',
                InputArgument::OPTIONAL,
                'Page name'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = $input->getArgument('page');
        $all = $input->getOption('all');

        if (! $all && empty($page)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "page" or "--all" option)') . '</error>');
            return (int) false;
        }

        $writer = $this->getProfileWriter($input);
        if (\Tiki_Profile_InstallHandler_WikiPage::export($writer, $page, $all)) {
            $writer->save();
        } else {
            $output->writeln("<error>Page not found: $page</error>");
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
