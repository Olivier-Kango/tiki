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
 * Smarty plugin to display wiki-parsed content
 *
 * Usage: {wiki}wiki text here{/wiki}
 * {wiki isHtml="true" }html text as stored by ckEditor here{/wiki}
 */
function smarty_block_wiki($params, $content, $smarty, &$repeat)
{
    if ($repeat) {
        return;
    }

    if ((isset($params['isHtml'])) and ($params['isHtml'] )) {
        $isHtml = true;
    } else {
        $isHtml = false;
    }

    $options = ['is_html' => $isHtml];
    if (isset($params['objectId'], $params['objectType'], $params['fieldName'])) {
        $options['objectType'] = $params['objectType'];
        $options['objectId'] = $params['objectId'];
        $options['fieldName'] = $params['fieldName'];
    }

    $ret = TikiLib::lib('parser')->parse_data($content, $options);
    if (isset($params['line']) && $params['line'] == 1) {
        $ret = preg_replace(['/<br \/>$/', '/[\n\r]*$/'], '', $ret);
    }
    return $ret;
}
