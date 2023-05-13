<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     quoted
 * Purpose:  quote text by adding ">" or using {QUOTE()} plugin
 * -------------------------------------------------------------
 */
function smarty_modifier_quoted($string, $format = 'simple', $replyto = '')
{
    if ($format == 'simple') {
        $string = str_replace("\n", "\n>", $string);
        $string = "\n>" . $string;
    } elseif ($format == 'fancy') {
        $string = "{QUOTE(replyto=>$replyto)}" . $string . '{QUOTE}';
    }
    return $string;
}
