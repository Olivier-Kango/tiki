<?php
// (c) Copyright 2002-2015 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_captcha_list()
{
    return array (
		'captcha_wordLen' => array(
			'name' => tra('Word length of the captcha image'),
            'description' => tra('Word length of the captcha image.').' '.tra('Default:'). '6',
			'type' => 'text',
			'default' => 6,
		),
		'captcha_width' => array(
			'name' => tra('Width of the captcha image in pixels'),
            'description' => tra('Width of the captcha image in pixels.').' '.tra('Default:'). '180',
			'type' => 'text',
			'default' => 180,
		),
		'captcha_noise' => array(
			'name' => tra('Level of noise of the captcha image'),
            'description' => tra('Level of noise of the captcha image.').' '.tra('Choose a smaller number for less noise and easier reading.').' '.tra('Default:'). '100',
            'type' => 'text',
            'default' => 100,
		),
	);
}
