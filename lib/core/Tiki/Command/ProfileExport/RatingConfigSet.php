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
    protected function configure()
    {
        $this
            ->setName('profile:export:rating-config-set')
            ->setDescription('Export all advanced rating configurations into a set')
            ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $writer = $this->getProfileWriter($input);

        if (\Tiki_Profile_InstallHandler_RatingConfigSet::export($writer)) {
            $writer->save();
        }
    }
}
