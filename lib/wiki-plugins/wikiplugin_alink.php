<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_alink_info()
{
    return [
        'name' => tra('Anchor Link'),
        'documentation' => 'PluginAlink',
        'description' => tra('Create a link to an anchor'),
        'prefs' => ['wikiplugin_alink'],
        'body' => tra('Anchor link label.'),
        'introduced' => 1,
        'iconname' => 'link',
        'tags' => [ 'basic' ],
        'params' => [
            'aname' => [
                'required' => true,
                'name' => tra('Anchor Name'),
                'description' => tra('The anchor name as defined in the Aname plugin.'),
                'default' => '',
                'since' => '1',
            ],
            'pagename' => [
                'required' => false,
                'name' => tra('Page Name'),
                'description' => tra('The name of the wiki page containing the anchor. If empty, the anchor name will be searched for on the wiki page where the plugin is used.'),
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
                'since' => '1',
            ],
        ],
    ];
}

function wikiplugin_alink($data, $params)
{
    global $prefs;
    $multilinguallib = TikiLib::lib('multilingual');
    $tikilib = TikiLib::lib('tiki');
    extract($params, EXTR_SKIP);

    if (! isset($aname)) {
        return ("<b>missing parameter for aname</b><br />");
    }

    $aname = $tikilib->urlFragmentString($aname);

    if (isset($pagename) && $pagename) {
        // Stolen, with some modifications, from tikilib.php line 4717-4723
        if ($desc = $tikilib->page_exists_desc($pagename)) {
        // to choose the best page language
            $bestLang = ($prefs['feature_multilingual'] == 'y' && $prefs['feature_best_language'] == 'y') ? "&amp;bl" : "";
        // $bestLang = $prefs['feature_best_language'] == 'y' ? "&amp;bl" : "";

            return "<a title=\"$desc\" href='tiki-index.php?page=" . urlencode($pagename) .
            $bestLang . "#" . $aname . "' class='wiki'>$data</a>";
        } else {
            return $data . '<a href="tiki-editpage.php?page=' . urlencode($pagename) .
            '" title="' . tra("Create page:") . ' ' . urlencode($pagename) .
            '"  class="wiki wikinew">?</a>';
        }
    } elseif (isset($_REQUEST['page'])) {
        $urlPrefix = "tiki-index.php?page=";
        if ($prefs['feature_sefurl'] == 'y') {
            $urlPrefix = "";
        }
        return "<a href=\"" . $urlPrefix . $_REQUEST["page"] . "#$aname\">$data</a>";
    } else {
        return "<a href=\"#$aname\">$data</a>";
    }
}
