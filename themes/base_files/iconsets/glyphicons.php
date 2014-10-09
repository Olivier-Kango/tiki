<?php 
// (c) Copyright 2002-2014 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//The default iconset associates icon names to icon fonts. It is used as the fallback for all other iconsets.


// This script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

$iconset = array(
	'_settings' => array(
		'iconset_name' => tr('Glyphicons'),
		'iconset_description' => tr('Glyphicon focused iconset, see http://getbootstrap.com/components/'),
		'icon_tag' => 'span',
	),
    'comments' => array(
        'class' => 'glyphicon glyphicon-comment',
    ),
    'delete' => array(
        'class' => 'glyphicon glyphicon-remove',
    ),
    'edit' => array(
        'class' => 'glyphicon glyphicon-edit',
    ),
	'export' => array( 
		'class' => 'glyphicon glyphicon-export',
	),
	'file-archive' => array( 
		'class' => 'glyphicon glyphicon-floppy-save',
	),
	'help' => array( 
		'class' => 'glyphicon glyphicon-question-sign',
	),
	'history' => array( 
		'class' => 'glyphicon glyphicon-time',
	),
	'import' => array( 
		'class' => 'glyphicon glyphicon-import',
	),
    'post' => array(
        'class' => 'glyphicon glyphicon-pencil',
    ),
    'print' => array(
        'class' => 'glyphicon glyphicon-print',
    ),
    'rss' => array(
        'class' => 'glyphicon glyphicon-bullhorn',
    ),
	'settings' => array(
        'class' => 'glyphicon glyphicon-cog',
    ),
    'stop-watching' => array(
        'class' => 'glyphicon glyphicon-close',
    ),
    'trash' => array(
        'class' => 'glyphicon glyphicon-trash',
    ),
    'watch' => array(
        'class' => 'glyphicon glyphicon-eye-open',
    ),
);