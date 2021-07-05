<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function module_file_galleries_info()
{
	return [
		'name' => tra('File Galleries'),
		'description' => tra('Displays links to file galleries.'),
		'prefs' => ['feature_file_galleries'],
		'params' => [],
	];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_file_galleries($mod_reference, $module_params)
{
	$filegallib = TikiLib::lib('filegal');
	$smarty = TikiLib::lib('smarty');

	$smarty->assign('tree', $filegallib->getTreeHTML());
}
