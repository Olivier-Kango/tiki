<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_GlobalSource_VisitsSource implements Search_GlobalSource_Interface
{
	private $statslib;

	function __construct()
	{
		$this->statslib = TikiLib::lib('stats');
	}

	function getProvidedFields()
	{
		return ['visits'];
	}

	function getGlobalFields()
	{
		return [];
	}

	function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
	{
		if ($objectType === 'wiki page') {
			$objectType = 'wiki';
		}
		$visits = $this->statslib->object_hits($objectId, $objectType);

		return [
			'visits' => $typeFactory->sortable($visits)
		];
	}
}
