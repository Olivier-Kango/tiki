<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\TikiDb\SanitizeEncoding;

class TikiDb_Table
{
	/** @var TikiDb_Pdo|TikiDb_Adodb $db */
	protected $db;
	protected $tableName;
	protected $autoIncrement;
	protected $errorMode = TikiDb::ERR_DIRECT;

	protected static $utf8FieldsCache = [];

	function __construct($db, $tableName, $autoIncrement = true)
	{
		$this->db = $db;
		$this->tableName = $tableName;
		$this->autoIncrement = $autoIncrement;
	}

	function useExceptions()
	{
		$this->errorMode = TikiDb::ERR_EXCEPTION;
	}

	/**
	 * Inserts a row in the table by building the SQL query from an array of values.
	 * The target table is defined by the instance. Argument names are not validated
	 * against the schema. This is only a helper method to improve code readability.
	 *
	 * @param $values array Key-value pairs to insert.
	 * @param $ignore boolean Insert as ignore statement
	 * @return array|bool|mixed
	 */
	function insert(array $values, $ignore = false)
	{
		$bindvars = [];
		$query = $this->buildInsert($values, $ignore, $bindvars);

		$result = $this->db->queryException($query, $bindvars);

		if ($this->autoIncrement) {
			if ($insertedId = $this->db->lastInsertId()) {
				return $insertedId;
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	/**
	 * @param array $data
	 * @param array $keys
	 * @return array|bool|mixed
	 */
	function insertOrUpdate(array $data, array $keys)
	{
		$insertData = array_merge($data, $keys);

		$bindvars = [];
		$query = $this->buildInsert($insertData, false, $bindvars);
		$query .= ' ON DUPLICATE KEY UPDATE ';
		$query .= $this->buildUpdateList($data, $bindvars);

		$result = $this->db->queryException($query, $bindvars);

		if ($this->autoIncrement) {
			if ($insertedId = $this->db->lastInsertId()) {
				return $insertedId;
			//Multiple actions in a query (e.g., INSERT + UPDATE) returns result class instead of the id number
			} elseif (is_object($result)) {
				return $result;
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	/**
	 * Deletes a single record from the table matching the provided conditions.
	 * Conditions use exact matching. Multiple conditions will result in AND matching.
	 * @param array $conditions
	 * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
	 */
	function delete(array $conditions)
	{
		$bindvars = [];
		$query = $this->buildDelete($conditions, $bindvars) . ' LIMIT 1';

		return $this->db->queryException($query, $bindvars);
	}

	/**
	 * Builds and performs and SQL update query on the table defined by the instance.
	 * This query will update a single record.
	 * @param array $values
	 * @param array $conditions
	 * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
	 */
	function update(array $values, array $conditions)
	{
		return $this->updateMultiple($values, $conditions, 1);
	}

	/**
	 * @param array $values
	 * @param array $conditions
	 * @param null $limit
	 * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
	 */
	function updateMultiple(array $values, array $conditions, $limit = null)
	{
		$bindvars = [];
		$query = $this->buildUpdate($values, $conditions, $bindvars);

		if (! is_null($limit)) {
			$query .= ' LIMIT ' . intval($limit);
		}

		return $this->db->queryException($query, $bindvars);
	}


	/**
	 * Deletes a multiple records from the table matching the provided conditions.
	 * Conditions use exact matching. Multiple conditions will result in AND matching.
	 *
	 * The method works just like delete, except that it does not have the one record
	 * limitation.
	 * @param array $conditions
	 * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
	 */
	function deleteMultiple(array $conditions)
	{
		$bindvars = [];
		$query = $this->buildDelete($conditions, $bindvars);

		return $this->db->queryException($query, $bindvars);
	}

	function fetchOne($field, array $conditions, $orderClause = null)
	{
		if ($result = $this->fetchRow([$field], $conditions, $orderClause)) {
			return reset($result);
		}

		return false;
	}

	function fetchCount(array $conditions)
	{
		return $this->fetchOne($this->count(), $conditions);
	}

	function fetchFullRow(array $conditions, $orderClause = null)
	{
		return $this->fetchRow($this->all(), $conditions, $orderClause);
	}

	function fetchRow(array $fields, array $conditions, $orderClause = null)
	{
		$result = $this->fetchAll($fields, $conditions, 1, 0, $orderClause);

		return reset($result);
	}

	function fetchColumn($field, array $conditions, $numrows = -1, $offset = -1, $order = null)
	{
		if (is_string($order)) {
			$order = [$field => $order];
		}

		$result = $this->fetchAll([$field], $conditions, $numrows, $offset, $order);

		$output = [];

		foreach ($result as $row) {
			$output[] = reset($row);
		}

		return $output;
	}

	function fetchMap($keyField, $valueField, array $conditions, $numrows = -1, $offset = -1, $order = null)
	{
		$result = $this->fetchAll([$keyField, $valueField], $conditions, $numrows, $offset, $order);

		$map = [];

		foreach ($result as $row) {
			$key = $row[$keyField];
			$value = $row[$valueField];

			$map[ $key ] = $value;
		}

		return $map;
	}

	/**
	 * Test if a condition exists in the database.
	 *
	 * @param array $conditions List of conditions that will be tested
	 *
	 * @return bool True if the condition exists, false otherwise
	 */

	function fetchBool(array $conditions = []): bool
	{

		$query = 'SELECT 1 FROM ' . $this->escapeIdentifier($this->tableName);
		$query .= $this->buildConditions($conditions, $bindvars);

		$result = $this->db->fetchAll($query, $bindvars, 1, -1, $this->errorMode);
		return !empty($result[0][1]);
	}

	function fetchAll(array $fields = [], array $conditions = [], $numrows = -1, $offset = -1, $orderClause = null)
	{
		$bindvars = [];

		$fieldDescription = '';

		foreach ($fields as $k => $f) {
			if ($f instanceof TikiDB_Expr) {
				$fieldDescription .= $f->getQueryPart(null);
				$bindvars = array_merge($bindvars, $f->getValues());
			} else {
				$fieldDescription .= $this->escapeIdentifier($f);
			}

			if (is_string($k)) {
				$fieldDescription .= ' AS ' . $this->escapeIdentifier($k);
			}

			$fieldDescription .= ', ';
		}

		$query = 'SELECT ';
		$query .= (! empty($fieldDescription)) ? rtrim($fieldDescription, ', ') : '*';
		$query .= ' FROM ' . $this->escapeIdentifier($this->tableName);
		$query .= $this->buildConditions($conditions, $bindvars);
		$query .= $this->buildOrderClause($orderClause);

		return $this->db->fetchAll($query, $bindvars, $numrows, $offset, $this->errorMode);
	}

	function expr($string, $arguments = [])
	{
		return new TikiDb_Expr($string, $arguments);
	}

	function all()
	{
		return [$this->expr('*')];
	}

	function count()
	{
		return $this->expr('COUNT(*)');
	}

	function sum($field)
	{
		return $this->expr("SUM(`$field`)");
	}

	function max($field)
	{
		return $this->expr("MAX(`$field`)");
	}

	function min($field)
	{
		return $this->expr("MIN(`$field`)");
	}

	function increment($count)
	{
		return $this->expr('$$ + ?', [$count]);
	}

	function decrement($count)
	{
		return $this->expr('$$ - ?', [$count]);
	}

	function greaterThan($value)
	{
		return $this->expr('$$ > ?', [$value]);
	}

	function lesserThan($value)
	{
		return $this->expr('$$ < ?', [$value]);
	}

	/**
	 * Retrieve values within a range. The vales given will be included.
	 *
	 * @param $values array Must be an array containing 2 strings
	 *
	 * @return TikiDb_Expr
	 */
	function between($values)
	{
		return $this->expr('$$ BETWEEN ? AND ?', $values);
	}

	function not($value)
	{
		if (empty($value)) {
			return $this->expr('($$ <> ? AND $$ IS NOT NULL)', [$value]);
		} else {
			return $this->expr('$$ <> ?', [$value]);
		}
	}

	function like($value)
	{
		return $this->expr('$$ LIKE ?', [$value]);
	}

	function unlike($value)
	{
		return $this->expr('$$ NOT LIKE ?', [$value]);
	}

	function exactly($value)
	{
		return $this->expr('BINARY $$ = ?', [$value]);
	}

	function in(array $values, $caseSensitive = false)
	{
		if (empty($values)) {
			return $this->expr('1=0', []);
		} else {
			return $this->expr(($caseSensitive ? 'BINARY ' : '') . '$$ IN(' . rtrim(str_repeat('?, ', count($values)), ', ') . ')', $values);
		}
	}

	function notIn(array $values, $caseSensitive = false)
	{
		if (empty($values)) {
			return $this->expr('1=0', []);
		} else {
			return $this->expr(($caseSensitive ? 'BINARY ' : '') . '$$ NOT IN(' . rtrim(str_repeat('?, ', count($values)), ', ') . ')', $values);
		}
	}

	function findIn($value, array $fields)
	{
		$expr = $this->like("%$value%");

		return $this->any(array_fill_keys($fields, $expr));
	}

	function concatFields(array $fields)
	{
		$fields = array_map([$this, 'escapeIdentifier'], $fields);
		$fields = implode(', ', $fields);

		$expr = '';
		if ($fields) {
			$expr = "CONCAT($fields)";
		}

		return $this->expr($expr);
	}

	function any(array $conditions)
	{
		$binds = [];
		$parts = [];

		foreach ($conditions as $field => $expr) {
			$parts[] = $expr->getQueryPart($this->escapeIdentifier($field));
			$binds = array_merge($binds, $expr->getValues());
		}

		return $this->expr('(' . implode(' OR ', $parts) . ')', $binds);
	}

	function sortMode($sortMode)
	{
		return $this->expr($this->db->convertSortMode($sortMode));
	}

	private function buildDelete(array $conditions, & $bindvars)
	{
		$query = "DELETE FROM {$this->escapeIdentifier($this->tableName)}";
		$query .= $this->buildConditions($conditions, $bindvars);

		return $query;
	}

	private function buildConditions(array $conditions, & $bindvars)
	{
		$query = " WHERE 1=1";

		foreach ($conditions as $key => $value) {
			$field = $this->escapeIdentifier($key);
			if ($value instanceof TikiDb_Expr) {
				$query .= " AND {$value->getQueryPart($field)}";
				$bindvars = array_merge($bindvars, $value->getValues());
			} elseif (empty($value)) {
				$query .= " AND ($field = ? OR $field IS NULL)";
				$bindvars[] = $value;
			} else {
				$query .= " AND $field = ?";
				$bindvars[] = $value;
			}
		}

		return $query;
	}

	private function buildOrderClause($orderClause)
	{
		if ($orderClause instanceof TikiDb_Expr) {
			return ' ORDER BY ' . $orderClause->getQueryPart(null);
		} elseif (is_array($orderClause) && ! empty($orderClause)) {
			$part = ' ORDER BY ';

			foreach ($orderClause as $key => $direction) {
				$part .= "`$key` $direction, ";
			}

			return rtrim($part, ', ');
		}
	}

	private function buildUpdate(array $values, array $conditions, & $bindvars)
	{
		$query = "UPDATE {$this->escapeIdentifier($this->tableName)} SET ";

		$query .= $this->buildUpdateList($values, $bindvars);
		$query .= $this->buildConditions($conditions, $bindvars);

		return $query;
	}

	private function buildUpdateList($values, & $bindvars)
	{
		$query = '';

		foreach ($values as $key => $value) {
			$field = $this->escapeIdentifier($key);
			if ($value instanceof TikiDb_Expr) {
				$query .= "$field = {$value->getQueryPart($field)}, ";
				$bindvars = array_merge($bindvars, SanitizeEncoding::filterMysqlUtf8($value->getValues(), $this->getUtf8Fields(), $key));
			} else {
				$query .= "$field = ?, ";
				$bindvars[] = SanitizeEncoding::filterMysqlUtf8($value, $this->getUtf8Fields(), $key);
			}
		}

		return rtrim($query, ' ,');
	}

	private function buildInsert($values, $ignore, & $bindvars)
	{
		$fieldDefinition = implode(', ', array_map([$this, 'escapeIdentifier'], array_keys($values)));
		$fieldPlaceholders = rtrim(str_repeat('?, ', count($values)), ' ,');

		if ($ignore) {
			$ignore = ' IGNORE';
		}

		$bindvars = array_merge($bindvars, array_values(SanitizeEncoding::filterMysqlUtf8($values, $this->getUtf8Fields())));
		return "INSERT$ignore INTO {$this->escapeIdentifier($this->tableName)} ($fieldDefinition) VALUES ($fieldPlaceholders)";
	}

	protected function escapeIdentifier($identifier)
	{
		return "`$identifier`";
	}

	/**
	 * return the list of fields that have charset utf8 (vs utf8mb4) in the current table
	 *
	 * @return mixed
	 */
	public function getUtf8Fields()
	{
		if (! isset(self::$utf8FieldsCache[$this->tableName])) {
			$sql = "SELECT COLUMN_NAME AS col FROM information_schema.`COLUMNS` WHERE table_schema = DATABASE()"
				. " AND TABLE_NAME = ? AND CHARACTER_SET_NAME = 'utf8'";
			$result = $this->db->fetchAll($sql, [$this->tableName]);
			$shortFormat = is_array($result) ? array_column($result, 'col') : [];
			self::$utf8FieldsCache[$this->tableName] = array_combine($shortFormat, $shortFormat);
		}

		return self::$utf8FieldsCache[$this->tableName];
	}
}
