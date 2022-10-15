<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TrackerExportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tracker:export')
            ->setDescription('Export a CSV file from a tracker using a tracker import-export format')
            ->addArgument(
                'tabularId',
                InputArgument::REQUIRED,
                'ID of tracker import-export format to use'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Location (full path) and/or a CSV file name to export'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Exporting tracker...');

        $lib = \TikiLib::lib('tabular');
        $info = $lib->getInfo($input->getArgument('tabularId'));

        $perms = \Perms::get('tabular', $info['tabularId']);
        if (! $info || ! $perms->tabular_export) {
            throw new \Exception('Tracker Export: Import-Export Format not found');
        }

        $fileName = $input->getArgument('filename');

        $tracker = \Tracker_Definition::get($info['trackerId']);

        if (! $tracker) {
            throw new \Exception('Tracker Export: Tracker not found');
        }

        $schema = new \Tracker\Tabular\Schema($tracker);
        $schema->loadFormatDescriptor($info['format_descriptor']);
        $schema->loadFilterDescriptor($info['filter_descriptor']);
        $schema->loadConfig($info['config']);

        $schema->validate();

        if (! $schema->getPrimaryKey()) {
            throw new \Exception(tr('Primary Key required'));
        }

        // this will throw exceptions and not return if there's a problem
        $source = new \Tracker\Tabular\Source\TrackerSource($schema, $tracker);

        if (! empty($fileName)) {
            $writer = new \Tracker\Tabular\Writer\CsvWriter($fileName);
        } elseif (! empty($info['odbc_config'])) {
            $writer = new \Tracker\Tabular\Writer\ODBCWriter($info['odbc_config']);
        } elseif (! empty($info['api_config'])) {
            $writer = new \Tracker\Tabular\Writer\APIWriter($info['api_config']);
        } else {
            throw new \Exception(tr('Tracker Export: No filename or remote import-export synchronization settings provided.'));
        }

        $result = $writer->write($source);

        \Feedback::printToConsole($output);

        if ($result) {
            $output->writeln(tr("Items succeeded: %0", $result['succeeded']));
            $output->writeln(tr("Items failed: %0", $result['failed']));
            $output->writeln(tr("Items skipped: %0", $result['skipped']));
            foreach ($result['errors'] as $error) {
                $output->writeln('<error>' . $error . '</error>');
            }
        }

        $output->writeln('Export done');

        return(0);
    }
}
