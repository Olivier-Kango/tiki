<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
global $prefs;

$section = "wiki page";
$section_class = "tiki_wiki_page manage";   // This will be body class instead of $section
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'editwiki'                  => 'text',            //get
        ],
    ],
];
require_once('tiki-setup.php');

if ($tiki_p_edit !== 'y') {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You need permission to edit pages in order to experiment on plugin LIST."));
    $smarty->display("error.tpl");
    die;
}

$editwiki = $_REQUEST['editwiki'] ?? '';

$listunparsed = '{LIST()}' . $editwiki . '{LIST}';
$listparsed = TikiLib::lib('parser')->parse_data(
    $listunparsed,
    [
                    'absolute_links' => true,
                    'noheaderinc' => true,
                    'suppress_icons' => true,
                    'process_wiki_paragraphs' => false
                ]
);
$smarty->assign_by_ref('listparsed', $listparsed);
$smarty->assign_by_ref('listtext', $editwiki);

$page = 'Experiment with plugin LIST';

$smarty->assign('page', $page);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->display('tiki-pluginlist_experiment.tpl');
