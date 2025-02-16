<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_showpages_info()
{
    return [
        'name' => tra('Show Pages'),
        'documentation' => 'PluginShowPages',
        'description' => tra('Find pages by searching within page names'),
        'prefs' => [ 'wikiplugin_showpages' ],
        'iconname' => 'search',
        'introduced' => 1,
        'params' => [
            'find' => [
                'required' => true,
                'name' => tra('Find'),
                'description' => tra('Search criteria'),
                'since' => '1',
                'default' => '',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Result Count'),
                'description' => tra('Maximum amount of results displayed.'),
                'since' => '1',
                'filter' => 'int',
                'default' => '',
            ],
            'display' => [
                'required' => false,
                'name' => tra('Display'),
                'description' => tra('Display page name and/or description. Both displayed by default.'),
                'since' => '1',
                'filter' => 'text',
                'default' => 'name|desc',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Name'), 'value' => 'name'],
                    ['text' => tra('Description'), 'value' => 'desc'],
                    ['text' => tra('Name & Description'), 'value' => 'name|desc']
                ]
            ]
        ]
    ];
}

function wikiplugin_showpages($data, $params)
{
    global $tikilib, $prefs;

    extract($params, EXTR_SKIP);
    if (! isset($find)) {
        return ("<b>missing find parameter for plugin SHOWPAGES</b><br />");
    }

    if (! isset($max)) {
        $max = -1;
    }

    if (! isset($display) || (strpos($display, 'name') === false && strpos($display, 'desc') === false)) {
        $display = 'name|desc';
    }

    $data = $tikilib->list_pages(0, $max, 'pageName_asc', $find, null, false);

    $text = '';

    foreach ($data["data"] as $page) {
        if (isset($prefs['feature_wiki_description']) && $prefs['feature_wiki_description'] == 'y' && strpos($display, 'desc') !== false) {
            $desc = $tikilib->page_exists_desc($page["pageName"]);
        } else {
            $desc = '';
        }
        $text .= "<a href=\"tiki-index.php?page=" . $page["pageName"] . "\" title=\"" . tra("Last modified by") . " " . $page["user"] . "\" class=\"wiki\">";
        $text .= (strpos($display, 'name') !== false || strlen($desc) == 0 ? $page["pageName"] : $desc);
        $text .= "</a>";
        $text .= (strpos($display, 'name') !== false && $desc !== $page["pageName"] && strlen($desc) > 0 ? " - $desc" : "");
        $text .= "<br />";
    }

    return $text;
}
