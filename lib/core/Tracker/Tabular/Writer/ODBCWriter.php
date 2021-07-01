<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Writer;

class ODBCWriter
{
	private $odbc_manager;

	function __construct($config)
	{
		$this->odbc_manager = new \Tracker\Tabular\ODBCManager($config);
	}

	function write(\Tracker\Tabular\Source\SourceInterface $source)
	{
		$schema = $source->getSchema();
		$schema->validate();

		$columns = $schema->getColumns();
		foreach ($source->getEntries() as $entry) {
			$row = [];
			$pk = null;
			$id = null;
			foreach ($columns as $column) {
				$row[$column->getRemoteField()] = $entry->render($column);
				if ($column->isPrimaryKey()) {
					$pk = $column->getRemoteField();
					$id = $row[$pk];
					if ($schema->isPrimaryKeyAutoIncrement()) {
						unset($row[$pk]);
					}
				}
			}
			$this->odbc_manager->replace($pk, $id, $row);
		}
	}

	/**
	 * Called after trackeritem save event, this method updates remote data source with local changes
	 */
	function sync(\Tracker\Tabular\Schema $schema, int $item_id, array $old_values, array $new_values)
	{
		$schema->validate();
		$columns = $schema->getColumns();

		// prepare the remote entry to replace - send only the following:
		// - changed values
		// - fields that do not store value in Tiki db like ItemsList (they might have changed as well)
		// - schema primary key (needed for remote updates but usually does not change locally, e.g. AutoIncrement)
		$entry = [];
		$pk = $schema->getPrimaryKey();
		if ($pk) {
			$pk = $pk->getField();
		}
		foreach ($new_values as $permName => $value) {
			if (! isset($old_values[$permName]) || $value != $old_values[$permName] || $permName == $pk) {
				$entry[$permName] = $value;
			} else {
				$field = $schema->getDefinition()->getFieldFromPermname($permName);
				if ($field && $field['type'] == 'l') {
					$entry[$permName] = $value;
				}
			}
		}

		$row = [];
		$pk = null;
		$id = null;
		foreach ($columns as $column) {
			if (! isset($entry[$column->getField()]) && ! $column->isPrimaryKey()) {
				continue;
			}
			$row[$column->getRemoteField()] = $column->render($entry[$column->getField()], ['itemId' => $item_id]);
			if ($column->isPrimaryKey()) {
				$pk = $column->getRemoteField();
				$id = $row[$pk];
				if ($schema->isPrimaryKeyAutoIncrement()) {
					unset($row[$pk]);
				}
			}
		}

		if ($pk) {
			$result = $this->odbc_manager->replace($pk, $id, $row);
		} else {
			$existing = [];
			foreach ($columns as $column) {
				if (isset($old_values[$column->getField()])) {
					$existing[$column->getRemoteField()] = $column->render($old_values[$column->getField()], ['itemId' => $item_id]);
				}
			}
			$result = $this->odbc_manager->replaceWithoutPK($existing, $row);
		}

		// map back the remote values to local field values
		$mapped = [];
		foreach ($columns as $column) {
			$permName = $column->getField();
			$remoteField = $column->getRemoteField();
			if (isset($result[$remoteField])) {
				$info = [];
				$column->parseInto($info, $result[$remoteField]);
				if (isset($info['fields'][$permName])) {
					$mapped[$permName] = $info['fields'][$permName];
				}
			}
		}
		return $mapped;
	}

	function delete($pk, $id) {
		return $this->odbc_manager->delete($pk, $id);
	}
}
