<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Index_Memory implements Search_Index_Interface
{
	private $data = [];
	private $lastQuery;
	private $lastOrder;
	private $lastStart;
	private $lastCount;

	function addDocument(array $data)
	{
		$this->data[] = $data;
	}

	function endUpdate()
	{
	}

	function invalidateMultiple(array $objectList)
	{
	}

	function find(Search_Query_Interface $query, $resultStart, $resultCount)
	{
		$this->lastQuery = $query->getExpr();
		$this->lastOrder = $query->getSortOrder();
		$this->lastStart = $resultStart;
		$this->lastCount = $resultCount;
		return new Search_ResultSet([], 0, $resultStart, $resultCount);
	}

	function getTypeFactory()
	{
		return new Search_MySql_TypeFactory;
	}

	function optimize()
	{
	}

	function destroy()
	{
		$this->data = [];
		return true;
	}

	function exists()
	{
		return count($this->data) > 0;
	}

	/**
	 * For test purposes.
	 */
	function size()
	{
		return count($this->data);
	}

	/**
	 * For test purposes.
	 */
	function getDocument($index)
	{
		return $this->data[$index];
	}

	/**
	 * For test purposes.
	 */
	function getLastQuery()
	{
		return $this->lastQuery;
	}

	/**
	 * For test purposes.
	 */
	function getLastOrder()
	{
		return $this->lastOrder;
	}

	/**
	 * For test purposes.
	 */
	function getLastStart()
	{
		return $this->lastStart;
	}

	/**
	 * For test purposes.
	 */
	function getLastCount()
	{
		return $this->lastCount;
	}
}
