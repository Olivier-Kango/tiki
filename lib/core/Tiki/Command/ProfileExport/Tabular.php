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
    name: 'profile:export:tabular',
    description: 'Export a tracker import-export format definition'
)]
class Tabular extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Export all import-export formats'
            )
            ->addArgument(
                'tabular',
                InputArgument::OPTIONAL,
                'Tabular ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tabularId = $input->getArgument('tabular');
        $all = $input->getOption('all');

        if (! $all && empty($tabularId)) {
            $output->writeln('<error>' . tra('Not enough arguments (missing: "tabular" or "--all" option)') . '</error>');
            return (int) false;
        }

        $ref = $input->getOption('reference');
        if ($ref && ! \Tiki_Profile::isValidReference($ref, true)) {
            $output->writeln('<error>The value provided for the parameter reference do not have the right format: ' . $ref . '</error>');
            return Command::INVALID;
        }

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_Tabular::export($writer, $tabularId, $all);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("Import-Export not found: $tabularId");
        }
        return Command::SUCCESS;
    }
}
