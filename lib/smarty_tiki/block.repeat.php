<?php

// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * File: block.repeat.php
 * Type: block
 * Name: repeat
 * Purpose: repeat a template block a given number of times
 * Parameters: count [required] - number of times to repeat
 * assign [optional] - variable to collect output
 * Author: Scott Matthewman <scott@matthewman.net>
 */
function smarty_block_repeat($params, $content, $smarty, &$repeat)
{
    if ($repeat || ! empty($content)) {
        $intCount = (int)$params['count'];
        if ($intCount < 0) {
            trigger_error("block: negative 'count' parameter");
            return;
        }

        $strRepeat = str_repeat($content, $intCount);
        if (! empty($params['assign'])) {
            $smarty->assign($params['assign'], $strRepeat);
        } else {
            return $strRepeat;
        }
    }
}
