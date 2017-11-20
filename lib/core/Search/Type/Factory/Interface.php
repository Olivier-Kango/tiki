<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Type_Factory_Interface
{
	// tokenized - indexed - unstored in database
	function plaintext($value);
	// wiki parsed before indexed - tokenized - indexed - unstored in database
	function wikitext($value);
	// not tokenized - indexed - stored in database
	function timestamp($value, $dateOnly = false);
	// not tokenized - indexed - stored in database
	function identifier($value);
	// not tokenized - indexed - stored in database
	function numeric($value);
	// tokenized - indexed - unstored in database
	function multivalue($values);
	// tokenized - indexed - unstored in database
	function object($values);
	// tokenized - indexed - unstored in database
	function nested($values);
	// tokenized - indexed - stored in database
	function sortable($value);
	// tokenized - indexed - unstored in database (?)
	function geopoint($value);
	// like object but - not indexed - not mapped
	function json($value);
}
