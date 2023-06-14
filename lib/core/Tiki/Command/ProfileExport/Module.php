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

class Module extends ObjectWriter
{
    protected static $defaultDescription = 'Export a module definition';
    protected function configure()
    {
        $this
            ->setName('profile:export:module')
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                'Module ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleId = $input->getArgument('module');

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Module::export($writer, $moduleId);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Module not found: $moduleId");
        }
        return Command::SUCCESS;
    }
}
