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
 * -------------------------------------------------------------
 * File:     block.translate.php
 * Type:     block
 * Name:     translate
 * Purpose:  translate a block of text
 * -------------------------------------------------------------
 * Note that the tr *prefilter* deals with most of the apparent calls to the tr block at compile time,
 * leaving only a few Smarty translations reach this block.
 */
function smarty_block_tr($params, $content, $smarty, &$repeat)
{

    if ($repeat || empty($content)) {
        return;
    }

    if (empty($params['lang'])) {
        $lang = '';
    } else {
        $lang = $params['lang'];
    }

    $args = [];
    foreach ($params as $key => $value) {
        if (preg_match('/_([[:digit:]])+/', $key, $matches)) {
            $args[$matches[1]] = $value;
        }
    }

    if (empty($params['interactive']) || $params['interactive'] == 'y') {
        return tra($content, $lang, false, $args);
    } else {
        return tra($content, $lang, true);
    }
}
