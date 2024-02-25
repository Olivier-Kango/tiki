<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Script\Event;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Allow to generate missing index.php files in each folder to prevent folder browsing for security reason.
 */
class GenerateIndexFiles
{
    public static function generate(Event $event)
    {
        $io = $event->getIO();

        $command = new \Tiki\Command\DevbuildwsconfsCommand();
        $input = new ArrayInput([
            'command' => $command->getName(),
            '--generate' => true,
        ]);
        $input->setInteractive(false);
        $application = new Application();
        $application->add($command);
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $commandOutput = $output->fetch();
        $io->write($commandOutput);
    }
}
