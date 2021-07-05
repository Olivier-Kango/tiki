<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 */
function smarty_block_popup_link($params, $content, $smarty, &$repeat)
{
	global $prefs;
	$headerlib = TikiLib::lib('header');

	if ($repeat) {
		return;
	}

	static $counter = 0;

	$linkId = 'block-popup-link' . ++$counter;
	$block = $params['block'];

	if ($repeat === false) {
		if ($prefs['feature_jquery'] == 'y') {
			$headerlib->add_js(
				<<<JS
\$(document).ready( function() {

	\$('#$block').hide();

	\$('#$linkId').click( function() {
		var block = \$('#$block');
		if ( block.css('display') == 'none' ) {
			//var coord = \$(this).offset();
			block.css( 'position', 'absolute' );
			//block.css( 'left', coord.left);
			//block.css( 'top', coord.top + \$(this).height() );
			show( '$block' );
		} else {
			hide( '$block' );
		}
	});
} );
JS
			);
		}

		$href = ' href="javascript:void(0)"';

		if (isset($params['class'])) {
			if ($params['class'] == 'button') {
				$html = '<a id="' . $linkId . '"' . $href . '>' . $content . '</a>';
				$html = '<span class="button">' . $html . '</span>';
			} else {
				$html = '<a id="' . $linkId . '"' . $href . '" class="' . $class . '">' . $content . '</a>';
			}
		}
		return $html;
	}
}
