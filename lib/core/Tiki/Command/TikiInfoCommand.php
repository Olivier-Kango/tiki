<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'tiki:info',
    description: 'Displays the Tiki and/or PHP version'
)]
class TikiInfoCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'tiki_php',
                InputArgument::OPTIONAL,
                tr('Displays the Tiki (tiki) or PHP version (php), empty to display both')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tikiPhpArgument = $input->getArgument('tiki_php');

        $TWV = new \TWVersion();
        $tikiVersion = $TWV->version;

        if (empty($tikiPhpArgument)) {
            $output->writeln("<info>PHP version: " . PHP_VERSION . "</info>");
            $output->writeln("<info>Tiki version: " . $tikiVersion . "</info>");
        } elseif ($tikiPhpArgument == 'php') {
            $output->writeln("<info>" . PHP_VERSION . "</info>");
        } elseif ($tikiPhpArgument == 'tiki') {
            $output->writeln("<info>" . $tikiVersion . "</info>");
        } else {
            $output->writeln("<info>Unknown argument '" . $tikiPhpArgument . "'</info>");
            return Command::INVALID;
        }
        return Command::SUCCESS;
    }
}
