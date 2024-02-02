<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_whats_related_info()
{
    return [
        'name' => tra('Related Items'),
        'description' => tra('Lists objects which share a category with the viewed object.'),
        'prefs' => ['feature_categories'],
        'params' => []
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_whats_related($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $categlib = TikiLib::lib('categ');
    $access = TikiLib::lib('access');

    if ($access->is_xml_http_request()) {
        $parsedUrl = parse_url($_SERVER['HTTP_REFERER']);
        $url = '/tiki-index.php?' . $parsedUrl['query'];
    } else {
        $url = $_SERVER['REQUEST_URI'];
    }

    $WhatsRelated = $categlib->get_link_related($url);
    $smarty->assign_by_ref('WhatsRelated', $WhatsRelated);
}
