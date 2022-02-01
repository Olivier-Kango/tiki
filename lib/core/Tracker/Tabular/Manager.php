<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular;

class Manager
{
    private $table;

    public function __construct(\TikiDb $db)
    {
        $this->table = $db->table('tiki_tabular_formats');
    }

    public function getList($conditions = [])
    {
        return $this->table->fetchAll(['tabularId', 'name', 'trackerId'], $conditions, -1, -1, 'name_asc');
    }

    public function getInfo($tabularId)
    {
        $info = $this->table->fetchFullRow(['tabularId' => $tabularId]);

        $info['format_descriptor'] = json_decode($info['format_descriptor'], true) ?: [];
        $info['filter_descriptor'] = json_decode($info['filter_descriptor'], true) ?: [];
        $info['config'] = json_decode($info['config'], true) ?: [];
        $info['odbc_config'] = json_decode($info['odbc_config'], true) ?: [];
        return $info;
    }

    public function create($name, $trackerId, $odbc_config = [])
    {
        return $this->table->insert([
            'name' => $name,
            'trackerId' => $trackerId,
            'format_descriptor' => '[]',
            'filter_descriptor' => '[]',
            'config' => json_encode([
                'simple_headers' => 0,
                'import_update' => 1,
                'ignore_blanks' => 0,
                'import_transaction' => 0,
                'bulk_import' => 0,
                'skip_unmodified' => 0,
                'encoding' => '',
            ]),
            'odbc_config' => json_encode($odbc_config),
        ]);
    }

    public function update($tabularId, $name, array $fields, array $filters, array $config, array $odbc_config = [])
    {
        return $this->table->update([
            'name' => $name,
            'format_descriptor' => json_encode($fields),
            'filter_descriptor' => json_encode($filters),
            'config' => json_encode([
                'simple_headers' => (int)! empty($config['simple_headers']),
                'import_update' => (int)! empty($config['import_update']),
                'ignore_blanks' => (int)! empty($config['ignore_blanks']),
                'import_transaction' => (int)! empty($config['import_transaction']),
                'bulk_import' => (int)! empty($config['bulk_import']),
                'skip_unmodified' => (int)! empty($config['skip_unmodified']),
                'encoding' => $config['encoding'],
            ]),
            'odbc_config' => json_encode($odbc_config)
        ], ['tabularId' => $tabularId]);
    }

    public function remove($tabularId)
    {
        return $this->table->delete(['tabularId' => $tabularId]);
    }

    public function syncItemSaved($args)
    {
        if (isset($args['skip_sync']) && $args['skip_sync']) {
            return;
        }

        $definition = \Tracker_Definition::get($args['trackerId']);
        $tabularId = $definition->getConfiguration('tabularSync');
        if (empty($tabularId)) {
            return;
        }
        $tabular = $this->getInfo($tabularId);
        if (empty($tabular['tabularId'])) {
            \Feedback::error(tr("Tracker remote synchronization configured with a tabular format that does not exist."));
            return;
        }
        $trklib = \TikiLib::lib('trk');
        $schema = $this->getSchema($definition, $tabular);

        try {
            $writer = new Writer\ODBCWriter($tabular['odbc_config']);
            $remote = $writer->sync($schema, $args['object'], $args['old_values_by_permname'], $args['values_by_permname']);
            foreach ($remote as $field => $value) {
                if (isset($args['values_by_permname'][$field])) {
                    if ($value != $args['values_by_permname'][$field]) {
                        $field = $definition->getFieldFromPermName($field);
                        $trklib->modify_field($args['object'], $field['fieldId'], $value);
                    }
                }
            }
        } catch (\Exception $e) {
            \Feedback::error(tr("Failed synchronizing local changes with remote data source. Please try making these changes again later or make the same changes remotely. Error: %0", $e->getMessage()));
        }
    }

    public function syncItemDeleted($args)
    {
        if (isset($args['skip_sync']) && $args['skip_sync']) {
            return;
        }

        $definition = \Tracker_Definition::get($args['trackerId']);
        $tabularId = $definition->getConfiguration('tabularSync');
        if (empty($tabularId)) {
            return;
        }
        $tabular = $this->getInfo($tabularId);
        if (empty($tabular['tabularId'])) {
            \Feedback::error(tr("Tracker remote synchronization configured with a tabular format that does not exist."));
            return;
        }
        if (empty($tabular['odbc_config']['sync_deletes'])) {
            return;
        }

        $schema = $this->getSchema($definition, $tabular);
        foreach ($schema->getColumns() as $column) {
            if ($column->isPrimaryKey()) {
                $field = $definition->getFieldFromPermName($column->getField());
                $id = $args['values'][$field['fieldId']] ?: null;
                if ($id) {
                    try {
                        $writer = new Writer\ODBCWriter($tabular['odbc_config']);
                        $writer->delete($column->getRemoteField(), $id);
                    } catch (\Exception $e) {
                        \Feedback::error(tr("Failed synchronizing local item delete with remote data source. Remote item might get reimported. Please try deleting again later or delete the item remotely. Error: %0", $e->getMessage()));
                    }
                    break;
                }
            }
        }
    }

    public function getSchema($definition, $tabular)
    {
        $schema = new Schema($definition);
        $schema->loadFormatDescriptor($tabular['format_descriptor']);
        $schema->loadFilterDescriptor($tabular['filter_descriptor']);
        $schema->loadConfig($tabular['config']);

        return $schema;
    }

    public function validateRemoteUnchanged($trackerId, $itemId)
    {
        $definition = \Tracker_Definition::get($trackerId);
        if (! $definition) {
            \Feedback::error(tr("Tracker not found: %0", $trackerId));
            return true;
        }

        $tabularId = $definition->getConfiguration('tabularSync', 0);
        if (! $tabularId) {
            \Feedback::error(tr("Tracker not congfigured for remote synchronization: %0", $trackerId));
            return true;
        }

        $tabular = $this->getInfo($tabularId);
        if (empty($tabular['tabularId'])) {
            \Feedback::error(tr("Tabular not found: %0", $tabularId));
            return true;
        }

        $schema = $this->getSchema($definition, $tabular);
        $item = \Tracker_Item::fromId($itemId);
        $item = $item->getData();
        $item = $item['fields'];

        try {
            $writer = new Writer\ODBCWriter($tabular['odbc_config']);
            $diff = $writer->compareRemote($schema, $itemId, $item);

            foreach ($diff as $field => $value) {
                $field = $definition->getFieldFromPermName($field);
                \TikiLib::lib('trk')->modify_field($itemId, $field['fieldId'], $value);
            }

            if ($diff) {
                return tr("Remote item has changed since your last page load. Overwriting remote data is disabled. You can copy your changes to a safe place, reload the entry and make the changes again. Here's the difference: %0", print_r($diff, 1));
            } else {
                return true;
            }
        } catch (\Exception $e) {
            return tr("Failed ensuring remote item is up to date with local data. Please check remote server connectivity and try again. Error: %0", $e->getMessage());
        }
    }
}
