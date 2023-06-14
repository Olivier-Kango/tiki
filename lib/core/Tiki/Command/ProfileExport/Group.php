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

class Group extends ObjectWriter
{
    protected static $defaultDescription = 'Export a group definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:group')
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Group Name'
            )
            ->addOption(
                'with-category',
                null,
                InputOption::VALUE_NONE,
                'Include category permissions'
            )
            ->addOption(
                'with-object',
                null,
                InputOption::VALUE_NONE,
                'Include object permissions (note: some object types may be missing)'
            )
            ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $writer = $this->getProfileWriter($input);
        $group = $input->getArgument('group');
        $category = $input->getOption('with-category');
        $object = $input->getOption('with-object');

        if (\Tiki_Profile_Installer::exportGroup($writer, $group, $category, $object)) {
            $writer->save();
        } else {
            $output->writeln("<error>Group '$group' not found.</error>");
        }
        return Command::SUCCESS;
    }
}
