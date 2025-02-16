<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/* {autocomplete element=$element type=$type }
 * Attach jQuery autocomplete to element/s
 *
 * Params:
 *
 *        element: Required (jQuery selector, and match multiple elements)
 *        type:    Required (defined in tiki-jquery.js -> $.fn.tiki
 *                 currently: pagename|groupname|username|usersandcontacts|userrealname|tag|icon|trackername)
 *        options: Optional further options for autocomplete fn
 *                 see http://docs.jquery.com/Plugins/Autocomplete/autocomplete#url_or_dataoptions
 *                 N.B. Will be wrapped in {} chars here to avoid smarty delimiter difficulties
 *
 */
class Autocomplete extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $headerlib = \TikiLib::lib('header');

        if ($prefs['elementplus_autocomplete'] !== 'y' && $prefs['feature_jquery_autocomplete'] !== 'y') {
            return '';
        }

        if (empty($params) || empty($params['element']) || empty($params['type'])) {
            return '';
        }

        if (! empty($params['options'])) {
            $options = ',{' . $params['options'] . '}';
        } else {
            $options = '';
        }

        if ($prefs['elementplus_autocomplete'] === 'y') {
            $content = 'autocomplete($("' . $params['element'] . '")[0], "' . $params['type'] . '"' . $options . ');';
        } else {
            $content = '$("' . $params['element'] . '").tiki("autocomplete", "' . $params['type'] . '"' . $options . ');';
        }
        $headerlib->add_jq_onready($content);
    }
}
