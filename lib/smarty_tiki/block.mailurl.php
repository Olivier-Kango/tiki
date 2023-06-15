<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * Smarty plugin to complete relative URLs used in mail templates to absolute ones
 *
 * Usage: {mailurl}relative-url.php{/mailurl}
 * works also with: {mailurl}{wiki_page|sefurl}{/mailurl}
 * and: {mailurl}absolute-url{/mailurl}
 */
function smarty_block_mailurl($params, $content, $smarty, &$repeat)
{
    if ($repeat) {
        return;
    }
    return TikiLib::lib('tiki')->tikiUrl($content);
}
