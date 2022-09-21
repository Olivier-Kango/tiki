<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/*
 * Smarty plugin to display content only to some groups
 */

use Tiki\Lib\core\Toolbar\ToolbarsList;

function smarty_function_toolbars($params, $smarty)
{
    global $prefs, $is_html, $tiki_p_admin, $tiki_p_admin_toolbars, $section;
    $default = [
        'comments' => 'n',
        'is_html' => $is_html,
        'section' => $section,
        'syntax' => $prefs['markdown_default'],
    ];
    $params = array_merge($default, $params);

    if ($prefs['javascript_enabled'] != 'y') {
        return '';
    }
    // filters some tools here depending on section
    $hidden = [];
    $switchableWysiwygSections = ['wiki page', 'blogs', 'newsletters', 'cms', 'webmail'];
    if (
        (empty($params['switcheditor']) && ! in_array($params['section'], $switchableWysiwygSections)) ||
        (! empty($params['switcheditor']) && $params['switcheditor'] !== 'y')
    ) {
        $hidden[] = 'switcheditor';
    }

    if ($tiki_p_admin != 'y' || $tiki_p_admin_toolbars != 'y') {
        $hidden[] = 'admintoolbar';
    }

    if (! isset($params['area_id'])) {
        $params['area_id'] = 'editwiki';
    }

    $list = ToolbarsList::fromPreference($params, $hidden);
    if (isset($params['_wysiwyg']) && $params['_wysiwyg'] == 'y') {
        return $list->getWysiwygArray();
    } else {
        return $list->getWikiHtml();
    }
}
