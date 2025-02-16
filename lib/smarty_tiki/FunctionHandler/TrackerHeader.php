<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/* params
   level: level of tag <h>
   title: title of the <h>
   toggle: o for open, c for close
   inTable: table class in a table otherwise will insert div
*/

class TrackerHeader extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $headerlib = \TikiLib::lib('header');
        $output = $js = '';
        static $trackerheaderStack = [];
        static $iTrackerHeader = 0;
        $last = count($trackerheaderStack);
        $default = ['level' => 3, 'inTable' => ''];
        $params = array_merge($default, $params);
        extract($params, EXTR_SKIP);

        if (! empty($inTable)) {
            $output .= '</table>';
        }
        while (! empty($last) && $level <= $trackerheaderStack[$last - 1]) { // need to close block
            $output .= "</div>";
            array_pop($trackerheaderStack);
            --$last;
        }
        if (! empty($title)) { // new header
            array_push($trackerheaderStack, $level);
            $output .= "<!--PUSH" . count($trackerheaderStack) . " -->";
            $id = "trackerHeader_$iTrackerHeader";
            $div_id = "block_$id";
            $output .= "<h$level id=\"$id\"";
            if (($toggle == 'o' || $toggle == 'c')) {
                $output .= ' class="' . ($toggle == 'c' ? 'trackerHeaderClose' : 'trackerHeaderOpen') . '"';
            }
            $output .= '>';
            $output .= "$title";
            $output .= "</h$level>";
            if (($toggle == 'o' || $toggle == 'c')) {
                $js = "\$('#$id').on('click', function(event){";
                $js .= "\$('#$div_id').toggle();";
                $js .= "\$('#$id').toggleClass('trackerHeaderClose');";
                $js .= "\$('#$id').toggleClass('trackerHeaderOpen');";
                $js .= "});";
                $headerlib->add_jq_onready($js);
                if ($toggle == 'c') {
                    $headerlib->add_jq_onready("\$('#$div_id').hide();");
                }
            }
            $output .= '<';
            $output .= (isset($inTable) && $inTable == 'y') ? 'tbody' : 'div';
            $output .= " id=\"$div_id\">";
            ++$iTrackerHeader;
        } else {
            $last = 0;
            $trackerheaderStack = [];
        }
        if (! empty($inTable)) {
            $output .= "<table class=\"$inTable\">";
        }
        return $output;
    }
}
