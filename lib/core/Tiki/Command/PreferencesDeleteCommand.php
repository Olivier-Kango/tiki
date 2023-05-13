<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

class PreferencesDeleteCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('preferences:delete')
            ->setDescription('Delete a preference')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Preference name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $preference = $input->getArgument('name');

        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');
        $tikilib = TikiLib::lib('tiki');
        $prefslib = TikiLib::lib('prefs');

        $preferenceInfo = $prefslib->getPreference($preference);

        if (empty($preferenceInfo)) {
            $output->write('<error>Preference not found.</error>');
            return;
        }

        $tikilib->delete_preference($preference);

        $output->writeln(sprintf('Preference %s was deleted', $preference));
        $logslib->add_action('feature', $preference, 'system', 'deleted');
        return Command::SUCCESS;
    }
}
