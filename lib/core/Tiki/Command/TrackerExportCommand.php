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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TrackerExportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tracker:export')
            ->setDescription('Export a CSV file from a tracker using a tracker tabular format')
            ->addArgument(
                'tabularId',
                InputArgument::REQUIRED,
                'ID of tracker tabular format to use'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Location (full path) and/or a file name to export - depending on the import-export format, this could be csv, json, ndjson or ical format.'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Query string formatted name=value key pairs of filters applied to the corresponding import-export format. Inspect the web UI partial export of this format in order to get the available filter names and possible values.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Exporting tracker...');

        $lib = \TikiLib::lib('tabular');
        $info = $lib->getInfo($input->getArgument('tabularId'));

        $perms = \Perms::get('tabular', $info['tabularId']);
        if (! $info || ! $perms->tabular_export) {
            throw new \Exception('Tracker Export: Tabular Format not found');
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

        if ($filter = $input->getOption('filter')) {
            $qs = implode('&', $filter);;
            parse_str($qs, $data);
            $inp = new \JitFilter($data);
            $collection = $schema->getFilterCollection();
            $collection->applyInput($inp);
            $search = \TikiLib::lib('unifiedsearch');
            $query = $search->buildQuery([
                'type' => 'trackeritem',
                'tracker_id' => $info['trackerId'],
            ]);
            $collection->applyConditions($query);
            $source = new \Tracker\Tabular\Source\QuerySource($schema, $query);
        } else {
            // this will throw exceptions and not return if there's a problem
            $source = new \Tracker\Tabular\Source\TrackerSource($schema, $tracker);
        }

        if (! empty($fileName)) {
            $writer = new \Tracker\Tabular\Writer\CsvWriter($fileName);
        } elseif (! empty($info['odbc_config'])) {
            $writer = new \Tracker\Tabular\Writer\ODBCWriter($info['odbc_config']);
        } else {
            throw new \Exception(tr('Tracker Export: No filename or remote tabular synchronization settings provided.'));
        }

        $writer->write($source);

        \Feedback::printToConsole($output);

        $output->writeln('Export done');

        return(0);
    }
}
