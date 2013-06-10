<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ResultSet extends ArrayObject
{
	private $count;
	private $estimate;
	private $offset;
	private $maxRecords;

	private $highlightHelper;
	private $filters = array();

	public static function create($list)
	{
		if ($list instanceof self) {
			return $list;
		} else {
			return new self($list, count($list), 0, count($list));
		}
	}

	function __construct($result, $count, $offset, $maxRecords)
	{
		parent::__construct($result);

		$this->count = $count;
		$this->estimate = $count;
		$this->offset = $offset;
		$this->maxRecords = $maxRecords;
	}

	function replaceEntries($list)
	{
		$return = new self($list, $this->count, $this->offset, $this->maxRecords);
		$return->estimate = $this->estimate;
		$return->highlightHelper = $this->highlightHelper;

		return $return;
	}

	function setHighlightHelper(Zend_Filter_Interface $helper)
	{
		$this->highlightHelper = $helper;
	}

	function setEstimate($estimate)
	{
		$this->estimate = (int) $estimate;
	}

	function getEstimate()
	{
		return $this->estimate;
	}

	function getMaxRecords()
	{
		return $this->maxRecords;
	}

	function getOffset()
	{
		return $this->offset;
	}

	function count()
	{
		return $this->count;
	}

	function highlight($content)
	{
		if ($this->highlightHelper) {
			// Build the content string based on heuristics
			$text = '';
			foreach ($content as $key => $value) {
				if ($key != 'object_type' // Skip internal values
				 && $key != 'object_id'
				 && $key != 'parent_object_type'
				 && $key != 'parent_object_id'
				 && $key != 'relevance'
				 && $key != 'url'
			     && $key != 'title'
				 && ! empty($value) // Skip empty
				 && ! is_array($value) // Skip arrays, multivalues fields are not human readable
				 && ! preg_match('/^[\w-]+$/', $value)) { // Skip anything that looks like a single token
					$text .= "\n$value";
				}
			}

			if (! empty($text)) {
				return $this->highlightHelper->filter($text);
			}
		}
	}

	function hasMore()
	{
		return $this->count > $this->offset + $this->maxRecords;
	}

	function getFacet(Search_Query_Facet_Interface $facet)
	{
		foreach ($this->filters as $filter) {
			if ($filter->isFacet($facet)) {
				return $filter;
			}
		}
	}

	function addFacetFilter(Search_ResultSet_FacetFilter $facet)
	{
		$this->filters[] = $facet;
	}
}

