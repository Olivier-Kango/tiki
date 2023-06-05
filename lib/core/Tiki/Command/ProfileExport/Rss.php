<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Rss extends ObjectWriter
{
    protected static $defaultDescription = 'Export an RSS Feed definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:rss')
            ->addArgument(
                'rss',
                InputArgument::REQUIRED,
                'RSS Feed ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('rss');

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Rss::export($writer, $id);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("RSS Feed not found: $id");
        }
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
