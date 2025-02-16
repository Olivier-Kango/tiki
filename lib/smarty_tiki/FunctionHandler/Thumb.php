<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * smarty_function_thumb handler: Display a thumbnail of a file/image (from a file gallery)
 *
 * params will be used as params for the HTML tag (e.g. border, class, ...), except special params starting with '_' :
 *  - _id: ID of the file
 *  - _max: Reduce image height and width to be less or equal the value of '_max' in pixels (keep ratio)
 */
class Thumb extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;

        if (! is_array($params) || ! isset($params['_id'])) {
            return;
        }

        if (! isset($params['_max'])) {
            $params['_max'] = $prefs['fgal_thumb_max_size']; // default thumbnail size
        }

        // Smarty html_image has some problems to detect height and width of such a file...
        //  $html = smarty_function_html_image(array(
        //      'src' => 'tiki-download_file.php?fileId='.((int)$params['_id']).'&amp;thumbnail&amp;max='.((int)$params['_max'])
        //  ), $smarty);

        $html = '<img ';
        foreach ($params as $k => $v) {
            if ($k == '' || $k[0] == '_' || $k == 'src') {
                continue;
            }
            $html .= ' ' . htmlentities($k) . '="' . htmlentities($v) . '"';
        }
        $html .= ' src="tiki-download_file.php?fileId=' . ((int) $params['_id']) . '&amp;thumbnail&amp;max=' . ((int) $params['_max']) . '" />';

        return $html;
    }
}
