<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Writer;

class APIWriter
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function write(\Tracker\Tabular\Source\SourceInterface $source)
    {
        $schema = $source->getSchema();
        $schema = $schema->getPlainOutputSchema();
        $schema->validate();

        $columns = $schema->getColumns();

        $succeeded = $failed = $skipped = 0;

        foreach ($source->getEntries() as $entry) {
            $row = [];

            $id = null;
            foreach ($columns as $column) {
                $row[$column->getLabel()] = $entry->render($column, false);
                if ($column->isPrimaryKey()) {
                    $id = $row[$column->getLabel()];
                }
            }

            foreach (explode('.', $this->config['modify_data_path']) as $field) {
                if ($field) {
                    $row = [$field => $row];
                }
            }

            if ($id) {
                if (! empty($this->config['update_url'])) {
                    $url = str_replace('#id', $id, $this->config['update_url']);
                    $client = new \Services_ApiClient($url, false);
                    $method = strtolower($this->config['update_method'] ?? 'patch');
                    $formatted_row = $this->formatRow(@$this->config['update_format'], $columns, $row);
                    $result = $client->$method('', $formatted_row);
                } else {
                    $skipped++;
                    continue;
                }
            } else {
                if (! empty($this->config['create_url'])) {
                    $url = $this->config['create_url'];
                    $client = new \Services_ApiClient($url, false);
                    $method = strtolower($this->config['create_method'] ?? 'post');
                    $formatted_row = $this->formatRow(@$this->config['create_format'], $columns, $row);
                    $result = $client->$method('', $formatted_row);
                } else {
                    $skipped++;
                    continue;
                }
            }

            if ($result && ! $id && method_exists($entry, 'backfillPK')) {
                foreach (explode('.', $this->config['modify_data_path']) as $field) {
                    if ($field && $result[$field]) {
                        $result = $result[$field];
                    }
                }
                $pk = null;
                foreach ($columns as $column) {
                    if ($column->isPrimaryKey()) {
                        $pk = $column->getField();
                        $id = $result[$column->getLabel()] ?? null;
                    }
                }
                if ($id && $pk) {
                    $entry->backfillPK($pk, $id);
                }
            }

            if ($result) {
                $succeeded++;
            } else {
                $failed++;
            }
        }
        return compact('succeeded', 'failed', 'skipped');
    }

    private function formatRow($format, $columns, $row)
    {
        if (! empty($format)) {
            $formatted_row = $format;
            foreach ($columns as $column) {
                $formatted_row = str_replace('%' . $column->getLabel() . '%', $row[$column->getLabel()], $formatted_row);
            }
        } else {
            $formatted_row = $row;
        }
        return $formatted_row;
    }
}
