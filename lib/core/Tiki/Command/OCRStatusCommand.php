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
    name: 'ocr:status',
    description: 'Give statistics on file OCR status (Queued, Processing, Finished, Skipped)'
)]
class OCRStatusCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ocrLib = \TikiLib::lib('ocr');

        //Retrieve the number of files marked as waiting to be processed.
        $count = $ocrLib->table('tiki_files')->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_PENDING]
        );
        $output->writeln("Queued files:       $count");

        $count = $ocrLib->table('tiki_files')->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_STALLED]
        );
        $output->writeln("Stalled files:      $count");

        $count = $ocrLib->table('tiki_files')->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_PROCESSING]
        );
        $output->writeln("Processing files:   $count");

        $count = $ocrLib->table('tiki_files')->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_FINISHED]
        );
        $output->writeln("Finished files:     $count");

        $count = $ocrLib->table('tiki_files')->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_SKIP]
        );
        $output->writeln("Will not OCR:       $count");
        return Command::SUCCESS;
    }
}
