<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin sortlinks handler
 * --------------------------------
 * Type: block
 * Name: sortlinks
 * Purpose: sort a list of options or links lines on the value of the line. Each line has the form <..>value</...>
 * inspiration : block repeat - Scott Matthewman <scott@matthewman.net>
 * -------------------------------------------------------------
 */
class SortLinks extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat || ! empty($content)) {
            $links = preg_split("/\n/", $content);
            $links2 = [];
            foreach ($links as $value) {
                preg_match('/.*(<[^>]*>)(.*)(<\/[^¨>]*>)/U', $value, $splitted);
    //    $splitted=preg_split("/[<>]/",$value,-1,PREG_SPLIT_NO_EMPTY);
                if (isset($splitted[2])) {
                    $splitted[2] = str_replace(["Î","É","È"], ['I','E','E'], $splitted[2]);
                    $links2[$splitted[2]] = $value;
                }
            }

            if (isset($params['case']) && $params['case'] == false) {
                uksort($links2, 'strcasecmp');
            } else {
                ksort($links2);
            }
            foreach ($links2 as $value) {
                echo $value;
            }
        }
    }
}
