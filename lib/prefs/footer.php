<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_footer_list()
{
	return [
		'footer_shadow_start' => [
			'name' => tra('Footer shadow div start'),
			'description' => tra(''),
			'type' => 'textarea',
			'size' => '2',
			'default' => '',
		],
		'footer_shadow_end' => [
			'name' => tra('Footer shadow div end'),
			'description' => tra(''),
			'type' => 'textarea',
			'size' => '2',
			'default' => '',
		],
	];
}
