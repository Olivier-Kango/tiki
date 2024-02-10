<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:file-gallery',
    description: 'Export a file gallery definition'
)]
class FileGallery extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addOption(
                'with-parents',
                null,
                InputOption::VALUE_NONE,
                'Includes all parents'
            )
            ->addOption(
                'deep',
                null,
                InputOption::VALUE_NONE,
                'Includes all children'
            )
            ->addOption(
                'include-files',
                null,
                InputOption::VALUE_NONE,
                'Includes files to export'
            )
            ->addArgument(
                'fileGallery',
                InputArgument::REQUIRED,
                'File Gallery ID'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $galId = $input->getArgument('fileGallery');
        $withParents = $input->getOption('with-parents');
        $includeFiles = $input->getOption('include-files');

        $deep = $input->getOption('deep');

        $writer = $this->getProfileWriter($input);

        $result = \Tiki_Profile_InstallHandler_FileGallery::export($writer, $galId, $withParents, $deep, $includeFiles);

        if ($result) {
            $writer->save();
        } else {
            $output->writeln("File gallery not found: $galId");
        }
        return Command::SUCCESS;
    }
}
