<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_last_modified_blogs_info()
{
    return [
        'name' => tra('Last-Modified Blogs'),
        'description' => tra('Displays the specified number of blogs, starting from the most recently modified.'),
        'prefs' => ["feature_blogs"],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_modified_blogs($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $bloglib = TikiLib::lib('blog');

    $ranking = $bloglib->list_blogs(0, $mod_reference["rows"], 'lastModif_desc', '', 'blog');

    $smarty->assign('modLastModifiedBlogs', $ranking["data"]);
}
