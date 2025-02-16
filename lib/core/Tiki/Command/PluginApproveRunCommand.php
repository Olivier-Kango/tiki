<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

/**
 * Command to approve a list of plugin usages
 */
class PluginApproveRunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('plugin:approve')
            ->setDescription(tr('Approve a list of plugin invocations/calls'))
            ->addArgument(
                'pluginFingerprints',
                InputArgument::OPTIONAL,
                tr('List fingerprints of the plugin invocations/calls to approve separated by commas')
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                tr('Approve all plugin invocations/calls')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');
        $parserLib = \TikiLib::lib('parser');
        $pluginFingerprints = $input->getArgument('pluginFingerprints');
        $all = $input->getOption('all');

        if (! $all && ! $pluginFingerprints) {
            $output->writeln(
                '<error>'
                . tr('You must either use the option --all or provide a list of fingerprints to approve.')
                . '</error>'
            );
            return Command::INVALID;
        }

        if ($all) {
            $logger->info(tr('Approving all pending plugins'));
            $parserLib->approve_all_pending_plugins();
            $logslib->add_action('plugin approve', 'system', 'system', 'Approved all pending plugins.');
        } elseif ($pluginFingerprints) {
            $logger->info(tr('Approving a list of plugins'));
            foreach (explode(',', $pluginFingerprints) as $fingerprint) {
                $logger->debug(tr('Approving plugin %0', $fingerprint));
                $parserLib->approve_selected_pending_plugings($fingerprint);
            }
            $logslib->add_action('plugin approve', 'system', 'system', count($pluginFingerprints) . ' plugins approved');
        }

        $output->writeln(tr('Plugins approved with success'));
        return Command::SUCCESS;
    }
}
