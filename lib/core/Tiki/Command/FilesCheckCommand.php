<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Files\CheckAttachmentGallery;
use Tiki\Files\CheckFileGallery;

/**
 * Command to check the status of the files in File and Image Gallery
 */
#[AsCommand(
    name: 'files:check',
    description: 'Detect orphan or extra files'
)]
class FilesCheckCommand extends Command
{
    /**
     * Command Execution entry point
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $checkFileGallery = new CheckFileGallery();
        $result = $checkFileGallery->analyse();
        $this->printResults($output, $result, tr('File Gallery'));

        $output->writeln('');

        $checkAttachmentGallery = new CheckAttachmentGallery('t');
        $result = $checkAttachmentGallery->analyse();
        $this->printResults($output, $result, tr('Tracker Attachments'));

        $output->writeln('');

        $checkAttachmentGallery = new CheckAttachmentGallery('w');
        $result = $checkAttachmentGallery->analyse();
        $this->printResults($output, $result, tr('Wiki Attachments'));

        $output->writeln('');

        $checkAttachmentGallery = new CheckAttachmentGallery('f');
        $result = $checkAttachmentGallery->analyse();
        $this->printResults($output, $result, tr('Forum Attachments'));
        return Command::SUCCESS;
    }

    /**
     * Helper function to print the results
     *
     * @param OutputInterface $output
     * @param array $result
     * @param string $title
     * @return void
     */
    protected function printResults($output, $result, $title)
    {
        $output->writeln('<info>== ' . $title . ' ==</info>');
        if ($result['usesDatabase']) {
            $output->writeln('<info>' . tr('Configured to stores files in Database') . '</info>');
        } else {
            $output->writeln('<info>' . tr('Configured to stores files on Disk: %0', implode(', ', $result['path'])) . '</info>');
        }
        if ($result['mixedLocation']) {
            $output->writeln('<comment>' . tr('Files are currently stored both in Disk and DB, it might mean that you changed the setting but did not migrate the files!') . '</comment>');
        }
        $output->writeln('<info>' . tr('Files in DB: %0', $result['countFilesDb']) . '</info>');
        $output->writeln('<info>' . tr('Files on Disk: %0', $result['countFilesDisk']) . '</info>');

        if ($result['issueCount'] === 0) {
            $output->writeln('<info>' . tr('No Issues found') . '</info>');
        } else {
            $output->writeln('<error>' . tr('Found %0 Issues, details below:', $result['issueCount']) . '</error>');
            if (count($result['missing']) > 0) {
                $output->writeln('<info>' . tr('The following files are missing') . '</info>');
                $table = new Table($output);
                $table->setHeaders([tr('Id'), tr('Name'), tr('Path')]);
                $table->setRows(array_map(function ($item) {
                    return [$item['id'], $item['name'], $item['path']];
                }, $result['missing']));
                $table->render();
            }
            if (count($result['mismatch']) > 0) {
                $output->writeln('<info>' . tr('The following files have different size that expected') . '</info>');
                $table = new Table($output);
                $table->setHeaders([tr('Id'), tr('Name'), tr('Path')]);
                $table->setRows(array_map(function ($item) {
                    return [$item['id'], $item['name'], $item['path']];
                }, $result['mismatch']));
                $table->render();
            }
            if (count($result['unknown']) > 0) {
                $output->writeln('<info>' . tr('The following files are unknown, exists in the folder, but not in the database') . '</info>');
                $table = new Table($output);
                $table->setHeaders([tr('Name'), tr('Path')]);
                $table->setRows(array_map(function ($item) {
                    return [$item['name'], $item['path']];
                }, $result['unknown']));
                $table->render();
            }
        }
    }
}
