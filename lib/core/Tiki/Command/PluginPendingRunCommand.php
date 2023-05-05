<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use TikiLib;
use TikiMail;

/**
 * Command to send notification if plugin is still not approved
 */
class PluginPendingRunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('plugin:pending')
            ->setDescription(tr('Send notification to users who can approve plugin'))
            ->addOption(
                'fingerprint',
                'f',
                InputOption::VALUE_REQUIRED,
                'Plugin fingerprint'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs;

        if (! $prefs['wikipluginprefs_pending_notification']) {
            $output->writeln(
                '<error>'
                . tr('Preference wikipluginprefs_pending_notification not enabled.')
                . '</error>'
            );
            return;
        }

        $logger = new ConsoleLogger($output);

        $logslib = TikiLib::lib('logs');
        $parserLib = TikiLib::lib('parser');
        $tikilib = TikiLib::lib('tiki');
        $fingerprint = $input->getOption('fingerprint');

        if (! $fingerprint) {
            $output->writeln(
                '<error>'
                . tr('You must provide a fingerprint to send notification.')
                . '</error>'
            );
            return;
        }

        $pluginInfo = $parserLib->getPluginInfo($fingerprint);

        if (! $pluginInfo) {
            $output->writeln(
                '<error>'
                . tr('Cannot find fingerprint.')
                . '</error>'
            );
            return;
        }

        if ($pluginInfo['status'] != 'pending') {
            $output->writeln(tr('Plugin approved already processed'));
            return;
        }

        $logger->info(tr('Sending plugin approval notification sent'));
        $logger->debug(tr('Sending ppproval notification for plugin %0', $fingerprint));

        $pluginInfo['name'] = explode('-', $fingerprint)[0];

        $tikilib->sendPluginApprovalNotificationEmail($pluginInfo);
        $logslib->add_action('plugin approval', 'system', 'system', 'Plugin approval notification sent');

        $output->writeln(tr('Plugin pending notification sent with success'));
        return Command::SUCCESS;
    }
}
