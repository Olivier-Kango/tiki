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

class Menu extends ObjectWriter
{
    protected static $defaultDescription = 'Export a menu definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:menu')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all menus'
            )
            ->addArgument(
                'menu',
                InputArgument::OPTIONAL,
                'Menu ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $menuId = $input->getArgument('menu');
        $all = $input->getOption('all');

        if (! $all && empty($menuId)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "menu" or "--all" option)') . '</error>');
            return (int) false;
        }

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Menu::export($writer, $menuId, $all);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Menu not found: $menuId");
        }
        return Command::SUCCESS;
    }
}
