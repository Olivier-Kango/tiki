<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RatingConfigSet extends ObjectWriter
{
    protected static $defaultDescription = 'Export all advanced rating configurations into a set';
    protected function configure()
    {
        $this
            ->setName('profile:export:rating-config-set')
            ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $writer = $this->getProfileWriter($input);

        if (\Tiki_Profile_InstallHandler_RatingConfigSet::export($writer)) {
            $writer->save();
        }
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
