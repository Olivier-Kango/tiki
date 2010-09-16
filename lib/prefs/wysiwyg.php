<?php
// (c) Copyright 2002-2010 by authors of the Tiki Wiki/CMS/Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_wysiwyg_list() {
	global $prefs;
	
	return array(
		'wysiwyg_optional' => array(
			'name' => tra('Wysiwyg Editor is optional'),
			'type' => 'flag',
			'dependencies' => array(
				'feature_wysiwyg',
			),
		),
		'wysiwyg_default' => array(
			'name' => tra('Wysiwyg Editor is displayed by default'),
			'type' => 'flag',
			'dependencies' => array(
				'feature_optional',
			),
		),
		'wysiwyg_memo' => array(
			'name' => tra('Reopen with the same editor'),
			'type' => 'flag',
		),
		'wysiwyg_wiki_parsed' => array(
			'name' => tra('Content is parsed like wiki page'),
			'description' => tra('This allows a mixture of wiki and html. All wiki syntax is parsed.'),
			'type' => 'flag',
		),
		'wysiwyg_wiki_semi_parsed' => array(
			'name' => tra('Content is partially wiki parsed'),
			'description' => tra('This also allows a mixture of wiki and html. Only some wiki syntax is parsed, such as plugins (not inline character styles etc).'),
			'type' => 'flag',
		),
		'wysiwyg_toolbar_skin' => array(
			'name' => tra('Wysiwyg editor skin'),
			'type' => 'list',
			'options' => $prefs['wysiwyg_ckeditor'] != 'y' ? array(
				'default' => tra('Default'),
				'office2003' => tra('Office 2003'),
				'silver' => tra('Silver'),
			) : array(
				'kama' => tra('Kama (Default)'),
				'office2003' => tra('Office 2003'),
				'v2' => tra('V2 (FCKEditor appearance)'),
			),
		),
		'wysiwyg_ckeditor' => array(
			'name' => tra('Use CKEditor'),
			'description' => tra('Experimental, new in Tiki 5: Use New CKEditor instead of previous FCKEditor'),
			'type' => 'flag',
		),
		'wysiwyg_htmltowiki' => array(
			'name' => tra('Use Wiki syntax in WYSIWYG'),
			'description' => tra('Experimental, new : Allow to keep the wiki syntax with the WYSIWYG editor. WARNING: plugin edit is not working in that case in WYSIWYG mode, use the Source mode instead '),
			'type' => 'flag',
			'warning' => tra('Experimental. This feature is still under development.'),
			'dependencies' => array(
				'ajax_autosave',
			),
		),
		'wysiwyg_fonts' => array(
			'name' => tra('Font names'),
			'description' => tra('List of font names separated by;'),
			'type' => 'textarea',
			'size' => '3',
		),
	);
}
