<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_listpages_info()
{
    return [
        'name' => tra('List Pages'),
        'documentation' => 'PluginListpages',
        'description' => tra('List pages based on various criteria'),
        'prefs' => ['wikiplugin_listpages', 'feature_listPages'],
        'iconname' => 'copy',
        'introduced' => 2,
        'params' => [
            'offset' => [
                'required' => false,
                'name' => tra('Result Offset'),
                'description' => tra('Result number at which the listing should start.'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => 0,
            ],
            'max' => [
                'required' => false,
                'name' => tra('Max'),
                'description' => tra('Limit number of items displayed in the list. Default is to display all.'),
                'since' => '2.0',
                'filter' => 'int',
                'default' => -1,
            ],
            'initial' => [
                'required' => false,
                'name' => tra('Initial'),
                'description' => tra('Initial page to show'),
                'since' => '2.0',
                'default' => '',
            ],
            'showNameOnly' => [
                'required' => false,
                'name' => tra('Show Name Only'),
                'description' => tra('Show only the page names'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showNameAndDescriptionOnly' => [
                'required' => false,
                'name' => tra('Show Name And Description Only'),
                'description' => tra('Show only the page names and descriptions'),
                'since' => '24.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'categId' => [
                'required' => false,
                'name' => tra('Category Filter'),
                'description' => tra('Filter categories by Id numbers. Use different separators to filter as follows:') . '<br />'
                    . '<code>:</code> - ' . tr('Page is in any of the specified categories. Example:') . ' <code>1:2:3</code><br />'
                    . '<code>+</code> - ' . tr('Page must be in all of the specified categories. Example:') . ' <code>1+2+3</code><br />'
                    . '<code>-</code> - ' . tr('Page is in the first specified category and not in any of the others. Example:')
                        . ' <code>1-2-3</code><br />',
                'since' => '2.0',
                'filter' => 'text',
                'accepted' => tra('Valid category ID or list separated by :, + or -'),
                'default' => '',
                'profile_reference' => 'category',
            ],
            'structHead' => [
                'required' => false,
                'name' => tra('Structure Head'),
                'description' => tra('Filter by structure head'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showPageAlias' => [
                'required' => false,
                'name' => tra('Show Page Alias'),
                'description' => tra('Show page alias in the list'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'includetag' => [
                'required' => false,
                'name' => tra('Include Tag'),
                'description' => tr('Only pages with specific tag (separate tags using %0)', '<code>;</code>'),
                'since' => '10.3',
                'advanced' => true,
            ],
            'excludetag' => [
                'required' => false,
                'name' => tra('Exclude Tag'),
                'description' => tr('Only pages with specific tag excluded (separate tags using %0)', '<code>;</code>'),
                'since' => '10.3',
                'advanced' => true,
            ],
            'showNumberOfPages' => [
                'required' => false,
                'name' => tra('Show Number of Pages'),
                'description' => tra('Show the number of pages matching criteria'),
                'since' => '10.3',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'find' => [
                'required' => false,
                'name' => tra('Find'),
                'description' => tra('Only pages with names similar to the text entered for this parameter will be listed'),
                'since' => '2.0',
            ],
            'lang' => [
                'required' => false,
                'name' => tra('Language'),
                'description' => tra('Two-letter language code to filter pages listed.'),
                'since' => '3.0',
                'filter' => 'alpha',
            ],
            'langOrphan' => [
                'required' => false,
                'name' => tra('Orphan Language'),
                'description' => tra('Two-letter language code to filter pages listed. Only pages not available in the
                    provided language will be listed.'),
                'since' => '3.0',
                'filter' => 'alpha',
            ],
            'translations' => [
                'required' => false,
                'name' => tra('Load Translations'),
                'description' => tra('User- or pipe-separated list of two-letter language codes for additional languages
                    to display. If the language parameter is not defined, the first element of this list will be used
                    as the primary filter.'),
                'since' => '3.0',
            ],
            'translationOrphan' => [
                'required' => false,
                'name' => tra('No translation'),
                'description' => tra('User- or pipe-separated list of two-letter language codes for additional languages
                    to display. List pages with no language or with a missing translation in one of the language'),
                'since' => '7.0',
            ],
            'exact_match' => [
                'required' => false,
                'name' => tra('Exact Match'),
                'description' => tra('Page name and text entered for the filter parameter must match exactly to be listed'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'only_orphan_pages' => [
                'required' => false,
                'name' => tra('Only Orphan Pages'),
                'description' => tra('Only list orphan pages'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'for_list_pages' => [
                'required' => false,
                'name' => tra('For List Pages'),
                'description' => '',
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'sort' => [
                'required' => false,
                'name' => tra('Sort'),
                'description' => tra('Sort ascending or descending on any field in the tiki_pages table. Syntax is
                    field name followed by _asc or _desc. Two examples:')
                    . ' <code>lastModif_desc</code> <code>pageName_asc</code>',
                'since' => '2.0',
                'filter' => 'text',
                'default' => 'pageName_asc',
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start'),
                'description' => tra('When only a portion of the page should be included, specify the marker from which
                    inclusion should start.'),
                'since' => '5.0',
                'default' => '',
            ],
            'end' => [
                'required' => false,
                'name' => tra('Stop'),
                'description' => tra('When only a portion of the page should be included, specify the marker at which
                    inclusion should end.'),
                'since' => '5.0',
                'default' => '',
            ],
            'length' => [
                'required' => false,
                'name' => tra('Length'),
                'description' => tra('Number of characters to display'),
                'since' => '5.0',
                'filter' => 'int',
                'default' => '',
            ],
            'showCheckbox' => [
                'required' => false,
                'name' => tra('Checkboxes'),
                'description' => 'Option to show checkboxes',
                'since' => '7.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'offset_arg' => [
                'required' => false,
                'name' => tra('Offset Argument'),
                'description' => 'Argument, id of the plugin (if more than one on the page), for pagination',
                'since' => '15.3',
                'advanced' => true
            ],
            'pagination' => [
                'required' => false,
                'name' => tra('Pagination'),
                'description' => 'Turn on pagination',
                'since' => '15.3',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'exclude_pages' => [
                'required' => false,
                'name' => tra('Exclude Page(s)'),
                'description' => tr('Wiki page names to be excluded from list'),
                'since' => '19.0',
                'filter' => 'pagename',
                'default' => '',
                'separator' => '|',
                'profile_reference' => 'wiki_page',
            ],
        ]
    ];
}

function wikiplugin_listpages($data, $params)
{
    global $prefs, $tiki_p_view;
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    if (isset($prefs)) {
        // Handle 1.10.x prefs
        $feature_listPages = $prefs['feature_listPages'];
        $feature_wiki = $prefs['feature_wiki'];
    } else {
        // Handle 1.9.x prefs
        global $feature_listPages, $feature_wiki;
    }

    if ($feature_wiki != 'y' || $feature_listPages != 'y' || $tiki_p_view != 'y') {
        // the feature is disabled or the user can't read wiki pages
        return '';
    }
    $default = [
        'offset' => 0,
        'max' => -1,
        'sort' => 'pageName_asc',
        'find' => '',
        'start' => '',
        'end' => '',
        'length' => -1,
        'translations' => null,
        'translationOrphan' => null,
        'showCheckbox' => 'y',
        'showNumberOfPages' => 'n',
        'for_list_pages' => 'y',
        'pagination' => 'n',
        'exclude_pages' => '',
    ];
    $params = array_merge($default, $params);
    extract($params, EXTR_SKIP);
    $filter = [];
    if (! isset($initial)) {
        if (isset($_REQUEST['initial'])) {
            $initial = $_REQUEST['initial'];
        } else {
            $initial = '';
        }
    }
    if (! empty($categId)) {
        if (strstr($categId, ':')) {
            $filter['categId'] = explode(':', $categId);
        } elseif (strstr($categId, '+')) {
            $filter['andCategId'] = explode('+', $categId);
        } elseif (strstr($categId, '-')) {
            $categories = explode('-', $categId);
            $filter['categId'] = array_shift($categories);
            $filter['notCategId'] = $categories;
        } else {
            $filter['categId'] = $categId;
        }
    }
    if (! empty($structHead) && $structHead == 'y') {
        $filter['structHead'] = $structHead;
    }
    if (! empty($translations) && $prefs['feature_multilingual'] == 'y') {
        $multilinguallib = TikiLib::lib('multilingual');
        if ($translations == 'user') {
            $translations = $multilinguallib->preferredLangs();
        } else {
            $translations = explode('|', $translations);
        }
    }
    if (! empty($translationOrphan)) {
        $filter['translationOrphan'] = explode('|', $translationOrphan);
    }
    if (! empty($langOrphan)) {
        $filter['langOrphan'] = $langOrphan;
    }
    if (! empty($lang)) {
        $filter['lang'] = $lang;
    } elseif (is_array($translations)) {
        $lang = $filter['lang'] = reset($translations);
    }
    if (! empty($lang)) {
        $filter['lang'] = $lang;
    } elseif (is_array($translations)) {
        $lang = $filter['lang'] = reset($translations);
    }
    if ($pagination == 'y') {
        if (! empty($offset_arg) && ! empty($_REQUEST[$offset_arg])) {
            $offset_pagination = $_REQUEST[$offset_arg];
        } else {
            $offset_pagination = 0;
        }
    }
    if (! empty($_REQUEST['sort_mode'])) {
        $sort = $_REQUEST['sort_mode'];
    }

    $exact_match = ( isset($exact_match) && $exact_match == 'y' );
    $only_name = ( isset($showNameOnly) && $showNameOnly == 'y' );
    $only_orphan_pages = ( isset($only_orphan_pages) && $only_orphan_pages == 'y' );
    $for_list_pages = ( isset($for_list_pages) && $for_list_pages == 'y' );
    $only_cant = false;

    $listpages = $tikilib->list_pages($offset, $max, $sort, $find, $initial, $exact_match, $only_name, $for_list_pages, $only_orphan_pages, $filter, $only_cant, '', $exclude_pages);
    if (! empty($includetag) || ! empty($excludetag)) {
        if (preg_match('/;/', $includetag)) {
            $aIncludetag = explode(';', $includetag);
        } else {
            $aIncludetag[] = $includetag;
        }
        if (preg_match('/;/', $excludetag)) {
            $aExcludetag = explode(';', $excludetag);
        } else {
            $aExcludetag[] = $excludetag;
        }
        $freetaglib = TikiLib::lib('freetag');
        $i = 0;

        foreach ($listpages['data'] as $page) {
            $bToRemove = true;
            $aListTags = $freetaglib->get_tags_on_object($page['pageName'], 'wiki page');
            if (! empty($aListTags['cant'])) {
                foreach ($aListTags['data'] as $aListTag) {
                    if (in_array($aListTag['tag'], $aExcludetag) && ! empty($aExcludetag[0])) {
                        unset($listpages['data'][$i]);
                        break;
                    }
                    if (in_array($aListTag['tag'], $aIncludetag) === true && ! empty($aIncludetag[0])) {
                        $bToRemove = false;
                    }
                }
            } elseif (! empty($aIncludetag[0])) {
                unset($listpages['data'][$i]);
            }
            if ($bToRemove && ! empty($aIncludetag[0])) {
                unset($listpages['data'][$i]);
            }
            $i++;
        }
        $listpages['data'] = array_merge($listpages['data']);
        unset($aIncludetag);
        unset($aExcludetag);
    }

    if (is_array($translations)) {
        $used = [];
        foreach ($listpages['data'] as &$page) {
            $pages = $multilinguallib->getTranslations('wiki page', $page['page_id']);

            $page['translations'] = [];
            foreach ($pages as $trad) {
                if ($trad['lang'] != $lang && in_array($trad['lang'], $translations)) {
                    $page['translations'][ $trad['lang'] ] = $trad['objName'];
                    $used[$trad['lang']] = $trad['langName'];
                }
            }
        }

        $smarty->assign('wplp_used', $used);
    }

    // Count how many pages are left after tag filtering
    $listpages['cant'] = count($listpages['data']);

    $smarty->assign_by_ref('checkboxes_on', $showCheckbox);
    $smarty->assign_by_ref('showNumberOfPages', $showNumberOfPages);
    if (! empty($showPageAlias) && $showPageAlias == 'y') {
        $smarty->assign_by_ref('showPageAlias', $showPageAlias);
    }

    if (! empty($start) || ! empty($end) || $length > 0) {
        foreach ($listpages['data'] as $i => $page) {
            $listpages['data'][$i]['snippet'] = $tikilib->get_snippet($page['data'], $page['outputType'], ! empty($page['is_html']), '', $length, $start, $end);
        }
    }
    if (isset($_REQUEST["page"])) {
        $smarty->assign("redirectTo", $_REQUEST["page"]);
    }

    // Count how many pages are left after sorting
    $smarty->assign("cant", $listpages['cant']);
    // The following two are for tiki-listpages_content.tpl (pagination)
    $smarty->assign("pluginlistpages", 'y');
    $smarty->assign("pagination", $pagination);
    if ($pagination == 'y') {
        // Show only x=$MaxRecords number of page entries on this page.
        for ($x = $offset_pagination; $x < ($offset_pagination + $GLOBALS['maxRecords']) && $x < count($listpages['data']); $x++) {
            $listpages_for_use[] = $listpages['data'][$x];
        }
        $smarty->assign_by_ref('listpages', $listpages_for_use);
        $smarty->assign("offset", $offset_pagination);
        $smarty->assign("offset_arg", $offset_arg);
    } else {
        $smarty->assign_by_ref('listpages', $listpages['data']);
    }

    // Display an error message if the $showNameAndDescriptionOnly and $showNameOnly options are all entered at the same time
    if (isset($showNameAndDescriptionOnly) && $showNameAndDescriptionOnly == 'y' && isset($showNameOnly) && $showNameOnly == 'y') {
        $smarty->assign('msg', tra("You cannot specify the showNameOnly and showNameAndDescriptionOnly options simultaneously, You must choose one of them."));
        $smarty->display("error.tpl");
        die;
    }

    if (isset($showNameOnly) && $showNameOnly == 'y') {
        $ret = $smarty->fetch('wiki-plugins/wikiplugin_listpagenames.tpl');
    } elseif (isset($showNameAndDescriptionOnly) && $showNameAndDescriptionOnly == 'y') {
        $ret = $smarty->fetch('wiki-plugins/wikiplugin_listpage_namesanddescription.tpl');
    } else {
        $ret = $smarty->fetch('tiki-listpages_content.tpl');
    }

    return '~np~' . $ret . '~/np~';
}
