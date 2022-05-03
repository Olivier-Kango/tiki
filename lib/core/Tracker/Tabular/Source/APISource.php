<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class APISource implements SourceInterface
{
    private $schema;
    private $config;

    public function __construct(\Tracker\Tabular\Schema $schema, array $api_config)
    {
        $this->schema = $schema;
        $this->config = $api_config;
    }

    public function getEntries()
    {
        $client = new \Services_ApiClient($this->config['list_url'], false);
        $result = $client->get();

        $data_path = explode('.', $this->config['list_data_path']);
        foreach ($data_path as $key => $field) {
            unset($data_path[$key]);
            if ($field) {
                if (! isset($result[$field])) {
                    throw new \Exception(tr("Error parsing API results path %0 at field %1.", $this->config['list_data_path'], $field));
                }
                $result = $result[$field];
            } else {
                break;
            }
        }

        if (! is_array($result)) {
            throw new \Exception(tr("Error parsing API response: result is not an array."));
        }

        foreach ($result as $num => $row) {
            foreach ($data_path as $field) {
                if (! isset($row[$field])) {
                    throw new \Exception(tr("Error parsing API results path %0 at field %1.", $this->config['list_data_path'], $field));
                }
                $row = $row[$field];
            }
            $data = [];
            foreach ($this->schema->getColumns() as $column) {
                if (isset($row[$column->getField()])) {
                    $data[spl_object_hash($column)] = $row[$column->getField()];
                    continue;
                }
                if (isset($row[$column->getLabel()])) {
                    $data[spl_object_hash($column)] = $row[$column->getLabel()];
                    continue;
                }
                throw new \Exception(tr('Expected field "%0" not found in record %1.', $column->getField(), $num));
            }
            yield new JsonSourceEntry($data);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
