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
    name: 'profile:export:article',
    description: 'Export an article definition'
)]
class Article extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'with-topic',
                null,
                InputOption::VALUE_NONE,
                'Includes article topic'
            )
            ->addOption(
                'with-type',
                null,
                InputOption::VALUE_NONE,
                'Includes article type'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all articles'
            )
            ->addArgument(
                'article',
                InputArgument::OPTIONAL,
                'Article ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('article');
        $withTopic = $input->getOption('with-topic');
        $withType = $input->getOption('with-type');
        $all = $input->getOption('all');

        if (! $all && empty($id)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "article" or "--all" options)') . '</error>');
            return (int) false;
        }

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Article::export($writer, $id, $withTopic, $withType, $all);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Article not found: $id");
        }
        return Command::SUCCESS;
    }
}
