<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ExtensionManager;
use TikiLib;

class PackageDisableCommand extends Command
{
    protected static $defaultDescription = 'Disable a Tiki Package';
    protected function configure()
    {
        $this
            ->setName('package:disable')
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'Tiki package name'
            )
            ->addOption(
                'revert',
                null,
                InputOption::VALUE_NONE,
                'Rollback profile changes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logslib = TikiLib::lib('logs');

        $io = new SymfonyStyle($input, $output);

        $availablePaths = [
            'vendor',
            'vendor_custom'
        ];

        $packageName = $input->getArgument('package');

        foreach ($availablePaths as $path) {
            if (file_exists($path . '/' . $packageName)) {
                $basePath = $path;
                break;
            }
        }

        if (empty($basePath)) {
            $io->error('No folder was found. Did you forgot to install');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $rollback = $input->getOption('revert');

        $success = ExtensionManager::disableExtension($packageName, $rollback);
        $messages = ExtensionManager::getMessages();
        $io->writeln(implode(PHP_EOL, $messages));
        $logslib->add_action('package disable', 'system', 'system', $packageName . ' package disabled.');

        if ($success) {
            $io->success(tr('Extension %0 is now disabled', $packageName));
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $io->error(tr('Extension %0 was not disabled.', $packageName));
        return \Symfony\Component\Console\Command\Command::FAILURE;
    }
}
