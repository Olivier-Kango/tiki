<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:rating-config-set',
    description: 'Export all advanced rating configurations into a set'
)]
class RatingConfigSet extends ObjectWriter
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $writer = $this->getProfileWriter($input);

        if (\Tiki_Profile_InstallHandler_RatingConfigSet::export($writer)) {
            $writer->save();
        }
        return Command::SUCCESS;
    }
}
