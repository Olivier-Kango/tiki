<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'installer:lock',
    description: 'Disable the installer',
)]
class InstallerLockCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp('Lock the installer so that users can\'t destroy the database through the browser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $out = <<<LOCK
This lock file was created with:

php console.php installer:lock

Don't remove or rename this file as it would re-enable the installer. The
installer allows a user to change or destroy the site’s database through the
browser so it is very important to keep it locked.

LOCK;
        $file = 'db/lock';
        if (! file_put_contents($file, $out)) {
            $output->writeln("<error>Could not lock installer</error>");
            return Command::FAILURE;
        } else {
            $output->writeln("<info>Installer locked</info>");
            return Command::SUCCESS;
        }
    }
}
