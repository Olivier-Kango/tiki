<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'backup:files',
    description: 'Create a backup of Tiki instance files',
)]
class BackupFilesCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to save backup (relative to console.php, or absolute)'
            )
            ->addArgument(
                'dateFormat',
                InputArgument::OPTIONAL,
                'Format to use for the date part of the backup file. Defaults to "Y-m-d_H-i-s" and uses the PHP date function format'
            )
            ->addOption(
                'storageonly',
                null,
                InputOption::VALUE_NONE,
                'Backup only storage directories (file galleries, attachments etc...)'
            )
            ->addOption(
                'nostorage',
                null,
                InputOption::VALUE_NONE,
                'Backup only the main directory (ignore linked file gallery folders etc...)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tikilib = \TikiLib::lib('tiki');

        $path = $input->getArgument('path');
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        if (! is_dir($path)) {
            $output->writeln('<error>Error: Provided path not found</error>');
            return Command::FAILURE;
        }

        $local = \Tiki\TikiInit::getCredentialsFile();
        if (! is_readable($local)) {
            $output->writeln('<error>Error: "' . $local . '" not readable.</error>');
            return Command::FAILURE;
        }

        $dateFormat = $input->getArgument('dateFormat');
        if (! $dateFormat) {
            $dateFormat = 'Y-m-d_H-i-s';
        }

        require $local;

        $root = getcwd();
        if (! $root) {
            $output->writeln('<error>Error: Unable to derive source path</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('storageonly')) {
            $source = '';
        } else {
            $source = escapeshellarg($root);
        }

        // get other directories
        if (! $input->getOption('nostorage')) {
            $query = "select distinct value from tiki_preferences where name like '%use_dir' union select att_store_dir from tiki_forums";
            $result = $tikilib->query($query);
            $storage = [];
            while ($res = $result->fetchRow()) {
                $storage[] = $res['value'];
            }
            foreach ($storage as $dir) {
                if (strpos($dir, '..') !== false) {
                    $output->writeln('<error>Warning: Unable to backup storage directory ' . $dir . ' (please use absolute path)</error>');
                    continue;
                }
                if (! empty($dir) && $input->getOption('storageonly') && substr($dir, 0, 1) != '/') {
                    $dir = $root . '/' . $dir;
                } elseif (! $dir || substr($dir, 0, 1) != '/') {
                    continue;
                }
                if (substr($dir, -1) == '/') {
                    $dir = substr($dir, 0, strlen($dir) - 1);
                }
                if (! is_dir($dir)) {
                    $output->writeln('<error>Warning: Unable to backup storage directory ' . $dir . ' (directory not found)</error>');
                    continue;
                }
                $source .= ' ' . escapeshellarg($dir);
            }
        }

        if (! $source) {
            $output->writeln('<error>Error: No backup sources found.</error>');
            return Command::FAILURE;
        }

        $tarLocation = $path . '/' . $dbs_tiki . '_' . date($dateFormat) . '.tar.bz2';
        $tar = escapeshellarg($tarLocation);
        $command = "tar -cjf $tar $source";
        exec($command);
        $output->writeln('<comment>Backup complete: ' . $tarLocation . '</comment>');
        return Command::SUCCESS;
    }
}
