<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'          => [
            'type'     => 'word',              //post
            'objId'    => 'word',              //post
            'save'     => 'bool',              //post
            'newtag'   => 'string',            //post
            'rootlang' => 'lang',              //get
            'offset'   => 'int',               //get
        ],
        'staticKeyFiltersForArrays' => [
            'additional_languages' => 'word',              //post
            'setlang'              => 'bool',              //post
            'clear'                => 'bool',              //post
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_feature(['feature_freetags', 'freetags_multilingual', 'feature_multilingual']);
$access->check_permission('tiki_p_freetags_tag');

// Set Default Request Values
$cat_type = $_REQUEST['type'] ?? 'wiki page';
$cat_objId = $_REQUEST['objId'] ?? '';

if ($cat_type != 'wiki page' && $cat_type != 'article') {
    $smarty->assign('msg', tra("Not supported yet."));
    $smarty->display("error.tpl");
    die;
}

$freetaglib = TikiLib::lib('freetag');
$multilinguallib = TikiLib::lib('multilingual');

// Check for invalid or missing objId and non-admin user permissions
function handleError($message, $smarty)
{
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', $message);
    $smarty->display("error.tpl");
    die;
}

if (! empty($cat_objId)) {
    $name = $tikilib->get_page_name_from_id($cat_objId);
    if (! $name) {
        $error_message = tra("Invalid or missing objId");
        handleError($error_message, $smarty);
    }
    $info = $tikilib->get_page_info($name);
} else {
    $error_message = $tiki_p_admin_freetags != 'y'
        ? tra("You do not have the permission that is needed to use this feature")
        : tra("Invalid or missing objId");
    handleError($error_message, $smarty);
}

// Assign Values to Smarty
$smarty->assign([
    'type'      => $cat_type,
    'objId'     => $cat_objId,
    'page_name' => $name,
    'data'      => $info,
]);

if (isset($_REQUEST['save'])) {
    // Process save
    if (isset($_REQUEST['setlang']) && is_array($_REQUEST['setlang'])) {
        foreach ($_REQUEST['setlang'] as $tagId => $lang) {
            if (! empty($lang)) {
                $freetaglib->set_tag_language($tagId, $lang);
            }
        }
    }

    if (
        isset($_REQUEST['newtag'])
        && isset($_REQUEST['rootlang'])
        && is_array($_REQUEST['newtag'])
        && is_array($_REQUEST['rootlang'])
    ) {
        foreach ($_REQUEST['newtag'] as $tagGroup => $list) {
            if (is_array($list) && array_key_exists($tagGroup, $_REQUEST['rootlang'])) {
                foreach ($list as $lang => $tag) {
                    $root = $_REQUEST['rootlang'][$tagGroup];
                    if (! array_key_exists($lang, $root)) {
                        continue;
                    }

                    $freetaglib->translate_tag($root[$lang], $tagGroup, $lang, $tag);
                }
            }
        }
    }

    if (isset($_REQUEST['clear']) && is_array($_REQUEST['clear'])) {
        foreach ($_REQUEST['clear'] as $tag) {
            $freetaglib->clear_tag_language_from_id($tag);
        }
    }
} else {
    // Form reload
    if (isset($_REQUEST['newtag'])) {
        $smarty->assign('newtags', $_REQUEST['newtag']);
    }
    if (isset($_REQUEST['setlang'])) {
        $smarty->assign('setlang', $_REQUEST['setlang']);
    }
}

$freetags_per_page = $prefs['maxRecords'];
if (array_key_exists('offset', $_REQUEST)) {
    $offset = (int)$_REQUEST['offset'];
} else {
    $offset = 0;
}
if ($offset < 0) {
    $offset = 0;
}
$smarty->assign('freetags_offset', $offset);
$smarty->assign('freetags_per_page', $freetags_per_page);

$languages = $multilinguallib->preferredLangs();
$used_languages = [];
foreach ($languages as $l) {
    $used_languages[$l] = true;
}
if (
    array_key_exists('additional_languages', $_REQUEST)
    && is_array($_REQUEST['additional_languages'])
) {
    foreach ($_REQUEST['additional_languages'] as $lang) {
        $used_languages[$lang] = true;
    }
}
$used_languages = array_keys($used_languages);
$langLib = TikiLib::lib('language');
$allLanguages = $langLib->list_languages();

// select roughly readable languages
$t_used_languages = [];
foreach ($allLanguages as $al) {
    foreach ($used_languages as $ul) {
        if (substr($al["value"], 0, 2) == substr($ul, 0, 2)) {
            $t_used_languages[] = $al["value"];
            break;
        }
    }
}
$used_languages = $t_used_languages;

$tagList = $freetaglib->get_object_tags_multilingual($cat_type, $cat_objId, $used_languages, $offset, $freetags_per_page);

$rootlangs = [];
foreach ($tagList as $tagGroup) {
    foreach ($tagGroup as $k => $tag) {
        if (isset($tag['tagset'])) {
            if ($tag['tagset'] == $tag['tagId']) {
                $rootlangs[$tag['tagset']] = $tag['lang'];
            }
        }
    }
}

$baseArgs = [
    'type'                 => $cat_type,
    'objId'                => $cat_objId,
    'additional_languages' => $used_languages,
];

$prev = http_build_query(array_merge($baseArgs, ['offset' => $offset - $freetags_per_page]), '', '&');
$next = http_build_query(array_merge($baseArgs, ['offset' => $offset + $freetags_per_page]), '', '&');

$smarty->assign('next', 'tiki-freetag_translate.php?' . $next);
if ($offset) {
    $smarty->assign('previous', 'tiki-freetag_translate.php?' . $prev);
} else {
    $smarty->assign('previous', '');
}

$smarty->assign([
    'tagList'          => $tagList,
    'languageList'     => $used_languages,
    'fullLanguageList' => $allLanguages,
    'rootlang'         => $rootlangs,
    'metatag_robots'   => 'NOINDEX, NOFOLLOW',
]);

// Display the template
$smarty->assign('mid', 'tiki-freetag-translate.tpl');
$smarty->display("tiki.tpl");
