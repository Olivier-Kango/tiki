<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Exception;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OCRAllCommand extends Command
{
    protected static $defaultDescription = 'OCR all queued files';
    protected function configure()
    {
        $this
            ->setName('ocr:all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ocrLib = \TikiLib::lib('ocr');
        $outputStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('error', $outputStyle);

        try {
            $ocrLib->checkOCRDependencies();
        } catch (Exception $e) {
            $output->writeln(
                '<error>' . $e->getMessage() . '</error>'
            );
            return Command::FAILURE;
        }

        //Retrieve the number of files marked as waiting to be processed.
        $db = $ocrLib->table('tiki_files');
        $queueCount = $db->fetchCount(
            ['ocr_state' => $ocrLib::OCR_STATUS_PENDING]
        );

        $progress = new ProgressBar($output, $queueCount + 1);
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $progress->setOverwrite(false);
        }
        $progress->setFormatDefinition(
            'custom',
            ' %current%/%max% [%bar%] -- %message%'
        );
        $progress->setFormat('custom');
        $progress->setMessage('Preparatory checks');
        $progress->start();
        $OCRCount = 0;

        // release old files that might have died while processing, and report as error
        $processingNum = $ocrLib->releaseAllProcessing();
        if ($processingNum) {
            $progress->setMessage(
                "<error>There were stale processing files, which are now released.  Run command again to perform OCR.</error>\n"
            );
            $progress->finish();
            return Command::FAILURE;
        }

        $ocrLib->setNextOCRFile();

        if (! $ocrLib->nextOCRFile) {
            $progress->setMessage("<comment>No files to OCR</comment>\n");
            $progress->finish();
            return Command::SUCCESS;
        }

        while ($ocrLib->nextOCRFile) {
            try {
                $progress->setMessage('OCR processing file id ' . $ocrLib->nextOCRFile);
                $progress->advance();
                $ocrLib->OCRfile();
                $output->write(': done');
                $OCRCount++;
            } catch (Exception $e) {
                $output->write(': <error>failed</error>');
                $output->write(": <error>" . $e->getMessage() . '</error>', OutputInterface::VERBOSITY_DEBUG);
            }
        }
        $progress->setMessage(
            "<comment>Finished the OCR of $OCRCount files.</comment>\n"
        );
        $progress->finish();
        return Command::SUCCESS;
    }
}
