<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

/**
 * To display with difference between two blocks of text, often wiki syntax or html
 *
 *  */
class WikiDiff extends Base
{
    public function handle($params, Template $template)
    {
        $defaults = [
            'object_id' => '',                  // int | string (for wiki page)
            'object_type' => 'wiki page',       // string object type, wiki page if empty
            'oldver' => '',                     // int|string   required version or date string (uses strtotime)
            'newver' => '',                     // int|string   version or date string, latest if empty  (uses strtotime)
            'curver' => '',                   // string  by default is yes to display the current version of the page
            'diff_style' => '',                 // string       one of the options for default_wiki_diff_style pref
            'show_version_info' => false,       // bool         hide the h2 heading "Comparing version X with version Y"
        ];

        $params = array_merge($defaults, $params);

        if (! $params['object_id']) {
            return '<span class="text-danger">' . tra('wikidiff: error - no object_id specified') . '</span>';
        }

        if (! $params['oldver']) {
            return '<span class="text-danger">' . tra('wikidiff: error - no old version specified') . '</span>';
        }

        if ($params['object_type'] === 'wiki page') {
            $info = TikiLib::lib('tiki')->get_page_info($params['object_id'], false);
            if (! $info) {
                return '<span class="text-danger">' . tr('wikidiff: error - page "%0" not found', $params['object_id']) . '</span>';
            }

            $smarty = TikiLib::lib('smarty');
            $histlib = TikiLib::lib('hist');

            if (! is_numeric($params['oldver'])) {
                $time = strtotime($params['oldver']);
                $oldver = $histlib->get_version_by_time($params['object_id'], $time, 'after');
            } else {
                $oldver = $params['oldver'];
            }

            if (! is_numeric($params['newver']) && ! empty($params['newver'])) {
                $time = strtotime($params['newver']);
                $newver = $histlib->get_version_by_time($params['object_id'], $time);
            } else {
                $newver = $params['newver'];
            }

            if ($params['curver'] && $params['curver'] == 'y') {
                $time = strtotime("now");
                $curver = $histlib->get_version_by_time($params['object_id'], $time);
            } else {
                $curver = 0;
            }
            $smarty->assign('hide_version_info', ! $params['show_version_info']);
            $smarty->assign('hide_example_wikidiff_plugin_syntax', true);

            histlib_helper_setup_diff($params['object_id'], $oldver, $newver, $params['diff_style'], $curver);

            $html = $smarty->fetch('pagehistory.tpl');
            return $html;
        } else {
            // TODO for other types, e.g. tracker items

            return '<span class="text-danger">' . tra('wikidiff: Error - only wiki pages supported currently') . '</span>';
        }
    }
}
