<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// If you put some traces in this script, and can't see them
// because the script automatically forwards to another URL
// with a call to header(), then you will not see the traces
// If you want to see the traces, set value below to true.
// WARNING: DO NOT COMMIT WITH TRUE!!!!
$dieInsteadOfForwardingWithHeader = false;
global $prefs;
require_once('lib/debug/Tracer.php');

$inputConfiguration = [
    [ 'staticKeyFilters' => [
        'page' => 'pagename',
        'returnto' => 'pagename',
        'watch' => 'digits',
    ] ],
];

$section = "wiki page";
$section_class = "tiki_wiki_page manage";   // This will be body class instead of $section
require_once('tiki-setup.php');
$wikilib = TikiLib::lib('wiki');
$structlib = TikiLib::lib('struct');
$notificationlib = TikiLib::lib('notification');
$editlib = TikiLib::lib('edit');

/**
 * @param $page
 * @param $page_info
 */
function guess_new_page_attributes_from_parent_pages($page, $page_info)
{
    global $prefs, $need_lang;
    $editlib = TikiLib::lib('edit');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    if (! $page_info) {
        //
        // This is a new page being created. See if we can guess some of its attributes
        // (ex: language) based on those of its parent pages.
        //
        $new_page_inherited_attributes =
            $editlib->get_new_page_attributes_from_parent_pages($page, $page_info);
        if (
            $editlib->user_needs_to_specify_language_of_page_to_be_created($page, $page_info)
            && isset($new_page_inherited_attributes['lang'])
        ) {
            //
            // Language is not set yet, but it COULD be guessed from parent pages.
            // So, set it.
            //
            $_REQUEST['lang'] = $new_page_inherited_attributes['lang'];
        }
        if ($editlib->user_needs_to_specify_language_of_page_to_be_created($page, $page_info, $new_page_inherited_attributes)) {
            //
            // Language of new page was not defined, and could not be guessed from the
            // parent pages. User will have to specify it explicitly.
            //
            $langLib = TikiLib::lib('language');
            $languages = $langLib->list_languages(false, true);
            if (count($languages) === 1) {
                $_REQUEST['lang'] = $languages[0]['value'];
                $need_lang = false;
            } else {
                if ($prefs['wiki_default_language'] !== '') {
                    $_REQUEST['lang'] = $prefs['wiki_default_language'];
                    $need_lang = false;
                } else {
                    $smarty->assign('languages', $languages);
                    $smarty->assign('default_lang', $prefs['language']);
                    $need_lang = true;
                    $smarty->assign('_REQUEST', $_REQUEST);
                }
            }
        }
    }
}

/**
 * @param $page_id
 * @return bool
 */
function translationsToThisPageAreInProgress($page_id)
{
    $multilinguallib = TikiLib::lib('multilingual');

    $translations_in_progress = $multilinguallib->getTranslationsInProgressFlags($page_id);
    $answer = count($translations_in_progress) > 0;
    return $answer;
}

function execute_module_translation()
{
    $smarty = TikiLib::lib('smarty');
// will show the language of the available translations. Chnage to 'n' to show the page name
    $params['show_language'] = 'y';
// flag to indicate that the module is appearing within the notification area of the edit page
    $params['from_edit_page'] = 'y';
    $params['nobox'] = 'y';
    $module_reference = [
        'name' => 'translation',
            'params' => [ 'show_language' => $params['show_language'], 'from_edit_page' => $params['from_edit_page'], 'nobox' => $params['nobox'] ]
    ];

    $modlib = TikiLib::lib('mod');

    $out = $modlib->execute_module($module_reference);
    $smarty->assign('content_of_update_translation_section', $out);
}

function possibly_set_pagedata_to_pretranslation_of_source_page()
{
    global $tracer;
    $multilinguallib = TikiLib::lib('multilingual');
    $smarty = TikiLib::lib('smarty');
    $editlib = TikiLib::lib('edit');

    if ($editlib->isNewTranslationMode()) {
        $source_page = $_REQUEST['source_page'];
        $possibly_pretranslated_content = $multilinguallib->partiallyPretranslateContentOfPage($source_page, $_REQUEST['lang']);
        $smarty->assign('pagedata', $possibly_pretranslated_content);
    }
}


$access->check_feature('feature_wiki');

if ($editlib->isNewTranslationMode() || $editlib->isUpdateTranslationMode()) {
    $translation_mode = 'y';
    $multilinguallib = TikiLib::lib('multilingual');
} else {
    $translation_mode = 'n';
}
$smarty->assign('translation_mode', $translation_mode);

// If page is blank (from quickedit module or wherever) tell user -- instead of editing the default page
// Dont get the page from default HomePage if not set (surely this would always be an error?)
if (empty($_REQUEST["page"])) {
    $smarty->assign('msg', tra("You must specify a page name, it will be created if it doesn't exist."));
    $smarty->display("error.tpl");
    die;
}

$max_pagename_length = TikiLib::lib('wiki')->max_pagename_length();
if (mb_strlen($_REQUEST["page"]) > $max_pagename_length) {
    //$_REQUEST["page"] = substr($_REQUEST["page"], 0, $max_pagename_length);
    $smarty->assign('msg', tra(tr("You have exceeded the number of characters allowed (158 max) for the page name field")));
    $smarty->display("error.tpl");
    die;
}

if (strtolower($_REQUEST["page"]) == 'sandbox' && $prefs['feature_sandbox'] !== 'y') {
    $smarty->assign('msg', tra("You can’t name a page 'Sandbox' because it is reserved for the Sandbox feature"));
    $smarty->display("error.tpl");
    die;
}

$page = $_REQUEST["page"];

if (isset($_REQUEST["description"])) {
    $max_pagedescription_length = 201;
    if (mb_strlen($_REQUEST["description"]) > $max_pagedescription_length) {
        $smarty->assign('msg', tra("The description of the page should not exceed 200 characters."));
        $smarty->display("error.tpl");
        die;
    }
}

// Copy namespace from structure parent page
if ($prefs['namespace_enabled'] === 'y') {
    if (isset($_REQUEST['current_page_id'])) {
        $s_page_info = $structlib->s_get_page_info($_REQUEST['current_page_id']);
        $s_suffix = '';
        if (isset($prefs['namespace_separator']) && ! empty($prefs['namespace_separator']) && strpos($s_page_info['pageName'], $prefs['namespace_separator']) !== false) {
            $split = explode($prefs['namespace_separator'], $s_page_info['pageName']);
            $s_suffix = reset($split);
        }
    }
}
if (! empty($s_suffix)) {
    $_REQUEST['namespace'] = $s_suffix;
}

if ($prefs['namespace_enabled'] == 'y' && isset($_REQUEST['namespace'])) {
    // Only prepend the namespace separator, if the page is missing a namespace
    $ns = $_REQUEST['namespace'] . $prefs['namespace_separator'];
    if (strpos($page, $ns, 0) === false) {
        $page = $ns . $page;
    }
}

$smarty->assign('page', $page);
$info = $tikilib->get_page_info($page);
$smarty->assign('quickedit', isset($_GET['quickedit']));

// 2010-01-26: Keep in active until translation refactoring is done.
if ($editlib->isNewTranslationMode() || $editlib->isUpdateTranslationMode()) {
     $editlib->prepareTranslationData();
}
$editlib->make_sure_page_to_be_created_is_not_an_alias($page, $info);
guess_new_page_attributes_from_parent_pages($page, $info);

if ($translation_mode === 'n' && isset($info['page_id']) ? translationsToThisPageAreInProgress($info['page_id']) : false) {
    $smarty->assign('prompt_for_edit_or_translate', 'y');
    include_once('modules/mod-func-translation.php');
    execute_module_translation();
} else {
    $smarty->assign('prompt_for_edit_or_translate', 'n');
}

// wysiwyg decision
include 'lib/setup/editmode.php';

$auto_query_args = ['wysiwyg','page_id','page', 'returnto', 'lang', 'hdr'];

$smarty->assign('page', $page);
// Permissions - first is it a new page to be inserted into structure?
if (isset($_REQUEST["current_page_id"]) && empty($info)) {
    if (empty($_REQUEST['page'])) {
        $smarty->assign('msg', tra("You must specify a page name, it will be created if it doesn't exist."));
        $smarty->display("error.tpl");
        die;
    }

    $structure_info = $structlib->s_get_structure_info($_REQUEST['current_page_id']);
    if (
        ($tiki_p_edit != 'y' && ! $tikilib->user_has_perm_on_object($user, $structure_info["pageName"], 'wiki page', 'tiki_p_edit'))
        ||
        (($tiki_p_edit_structures != 'y' &&
            ! $tikilib->user_has_perm_on_object($user, $structure_info["pageName"], 'wiki page', 'tiki_p_edit_structures')))
    ) {
        $smarty->assign('errortype', 401);
        $smarty->assign('msg', tra("You do not have permission to edit this page."));
        $smarty->display("error.tpl");
        die;
    }

    $smarty->assign('current_page_id', $_REQUEST["current_page_id"]);
    if (isset($_REQUEST["add_child"])) {
        $smarty->assign('add_child', "true");
    }
} else {
    $structure_info = [];
    $smarty->assign('current_page_id', 0);
    $smarty->assign('add_child', false);
}
$tikilib->get_perm_object($page, 'wiki page', $info, true);
if ($tiki_p_edit !== 'y' && (! empty($info) || empty($structure_info))) {
    if (empty($user)) {
        $cachelib = TikiLib::lib('cache');
        $cacheName = $tikilib->get_ip_address() . $tikilib->now;
        $cachelib->cacheItem($cacheName, http_build_query($_REQUEST, '', '&'), 'edit');
        $smarty->assign('urllogin', "tiki-editpage.php?cache=$cacheName");
    }
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have permission to edit this page."));
    $smarty->display("error.tpl");
    die;
}
// Anti-bot feature: if enabled, anon user must type in a code displayed in an image
if (isset($_REQUEST['save']) && (! $user || $user === 'anonymous') && $prefs['feature_antibot'] === 'y') {
    if (! $captchalib->validate()) {
        $smarty->assign('errortype', 'no_redirect_login');
        $smarty->assign('msg', $captchalib->getErrors());
        $smarty->display("error.tpl");
        die;
    }
}

$page_ref_id = '';
if (isset($_REQUEST["page_ref_id"])) {
    $page_ref_id = $_REQUEST["page_ref_id"];
}

$smarty->assign('page_ref_id', $page_ref_id);

/**
 * @param $a1
 * @param $a2
 * @return mixed
 */
function compare_import_versions($a1, $a2)
{
    return $a1["version"] - $a2["version"];
}

$serviceLib = TikiLib::lib('service');
if (isset($_REQUEST['cancel_edit'])) {
    if ($prefs['feature_warn_on_edit'] === 'y') {
        $serviceLib->internal('semaphore', 'unset', ['object_id' => $page]);
    }
    if (! empty($_REQUEST['returnto'])) {
        if (isURL($_REQUEST['returnto'])) {
            $url = $_REQUEST['returnto'];
        } else {
            // came from wikiplugin_include.php edit button
            $url = $wikilib->sefurl($_REQUEST['returnto']);
        }
    } else {
        $url = $wikilib->sefurl($page);
        if (! empty($_REQUEST['page_ref_id'])) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'page_ref_id=' . $_REQUEST['page_ref_id'];
        }
    }

    if ($prefs['feature_multilingual'] === 'y' && $prefs['feature_best_language'] === 'y' && isset($info['lang']) && $info['lang'] !== $prefs['language']) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'no_bl=y';
    }

    if ($dieInsteadOfForwardingWithHeader) {
        die("-- tiki-editpage: Dying before first call to header(), so we can see traces. Forwarding to: \$url='$url'");
    }
    $access->redirect($url);
}
if (isset($_REQUEST['minor'])) {
    $_REQUEST['isminor'] = 'on';
    $_REQUEST['save'] = true;
}

if ($user && $prefs['feature_user_watches'] === 'y') {
    $isFormSubmit = isset($jitRequest['edit']);
    if ($tikilib->page_exists($page)) {
        $currentlyWatching = (bool) $tikilib->user_watches($user, 'wiki_page_changed', $page, 'wiki page');
        $default = $currentlyWatching;
    } else {
        // New pages get default watch checked for authors
        $currentlyWatching = false;
        $default = ($prefs['wiki_watch_author'] === 'y');
    }

    $requestedWatch = isset($_REQUEST['watch']) && $isFormSubmit;
    $smarty->assign('show_watch', 'y');
    $smarty->assign('watch_checked', ( ($default && ! $isFormSubmit) || $requestedWatch) ? 'y' : 'n');
} else {
    $currentlyWatching = false;
    $requestedWatch = false;
    $smarty->assign('show_watch_controls', 'n');
}

if (isset($_REQUEST['partial_save'])) {
    $_REQUEST['save'] = true;
}

if (isset($_REQUEST['hdr'])) {
    $smarty->assign('hdr', $_REQUEST['hdr']);
}

if (isset($_REQUEST['pos'])) {
    $smarty->assign('pos', $_REQUEST['pos']);
}

if (isset($_REQUEST['cell'])) {
    $smarty->assign('cell', $_REQUEST['cell']);
}

// We set empty wiki page name as default here if not set (before including Tiki modules)
if ($prefs['feature_warn_on_edit'] === 'y') {
    $editpageconflict = 'n';
    $beingEdited = 'n';
    $semUser = '';
    $u = $user ? $user : 'anonymous';
    if (! empty($page) && ($page !== 'sandbox' || $page === 'sandbox' && $tiki_p_admin === 'y')) {
        if (! isset($_REQUEST['save'])) {
            if (
                $serviceLib->internal('semaphore', 'is_set', ['object_id' => $page]) &&
                $serviceLib->internal('semaphore', 'get_user', ['object_id' => $page]) !== $u &&
                ! $serviceLib->internal('semaphore', 'is_set', ['object_id' => 'togetherjs ' . $page])
            ) {
                $editpageconflict = 'y';
            } elseif ($tiki_p_edit === 'y') {
                $serviceLib->internal('semaphore', 'set', ['object_id' => $page]);
            }
            $semUser = $serviceLib->internal('semaphore', 'get_user', ['object_id' => $page]);
            $beingedited = 'y';
        } else {
            $serviceLib->internal('semaphore', 'unset', ['object_id' => $page]);
            if ($serviceLib->internal('semaphore', 'is_set', ['object_id' => 'togetherjs ' . $page])) {
                $serviceLib->internal('semaphore', 'unset', ['object_id' => 'togetherjs ' . $page]);
            }
        }
    }
    if ($editpageconflict === 'y' && ! isset($_REQUEST["conflictoverride"])) {
        include_once('lib/smarty_tiki/modifier.userlink.php');
        include_once('lib/smarty_tiki/modifier.username.php');
        $msg = tr("This page is being edited by %0. Please check with the user before editing the page,    otherwise the changes will be stored as two separate versions in the history and you will have to manually merge them later.", smarty_modifier_username($semUser));
        $msg .= '<br /><br /><a href="tiki-editpage.php?page=';
        $msg .= urlencode($page);
        $msg .= '&conflictoverride=y">' . tra('Override lock and carry on with edit') . '</a>';
        $smarty->assign('msg', $msg);
        $smarty->assign('errortitle', tra('Page is currently being edited'));
        $smarty->display("error.tpl");
        die;
    }
}
$included_by = $wikilib->get_external_includes($page);
if (sizeof($included_by) > 0) {
    $smarty->assign_by_ref('included_by', $included_by);
}

$recursive_include = in_array($page, array_column($included_by, 'itemId'));
if ($recursive_include) {
    $smarty->assign('recursive_include', 'y');
}

$category_needed = false;
$contribution_needed = false;
if (isset($_REQUEST['lock_it']) && $_REQUEST['lock_it'] === 'on') {
    $lock_it = 'y';
} else {
    $lock_it = 'n';
}
if (isset($_REQUEST['comments_enabled']) && $_REQUEST['comments_enabled'] === 'on') {
    $comments_enabled = 'y';
} else {
    $comments_enabled = 'n';
}
$hash = [];
$hash['lock_it'] = $lock_it;
$hash['comments_enabled'] = $comments_enabled;

if (! empty($_REQUEST['contributions'])) {
    $hash['contributions'] = $_REQUEST['contributions'];
}
if (! empty($_REQUEST['contributors'])) {
    $hash['contributors'] = $_REQUEST['contributors'];
}
if (isset($_FILES['userfile1']) && is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
    $access->checkCsrf();
    require("lib/mail/mimelib.php");
    $fp = fopen($_FILES['userfile1']['tmp_name'], "rb");
    $data = '';
    while (! feof($fp)) {
        $data .= fread($fp, 8192 * 16);
    }
    fclose($fp);
    $name = $_FILES['userfile1']['name'];
    $mimelib = new mime();
    $output = $mimelib->decode($data);
    $parts = [];
    parse_output($output, $parts, 0);
    $last_part = '';
    $last_part_ver = 0;
    usort($parts, 'compare_import_versions');
    foreach ($parts as $part) {
        if ($part["version"] > $last_part_ver) {
            $last_part_ver = $part["version"];
            $last_part = $part["body"];
        }
        if (isset($part["pagename"])) {
            $pagename = urldecode($part["pagename"]);
            $version = urldecode($part["version"]);
            $author = urldecode($part["author"]);
            $lastmodified = $part["lastmodified"];
            if (isset($part["description"])) {
                $description = $part["description"];
            } else {
                $description = '';
            }
            $pageLang = isset($part["lang"]) ? $part["lang"] : "";
            $authorid = urldecode($part["author_id"]);
            if (isset($part["hits"])) {
                $hits = urldecode($part["hits"]);
            } else {
                $hits = 0;
            }
            $ex = substr($part["body"], 0, 25);
            //print(strlen($part["body"]));
            $msg = '';
            if (isset($_REQUEST['save']) && $prefs['feature_contribution'] === 'y' && $prefs['feature_contribution_mandatory'] === 'y' && (empty($_REQUEST['contributions']) || count($_REQUEST['contributions']) <= 0)) {
                $contribution_needed = true;
                $smarty->assign('contribution_needed', 'y');
            } else {
                $contribution_needed = false;
            }
            if (isset($_REQUEST['save']) && $prefs['feature_categories'] === 'y' && $prefs['feature_wiki_mandatory_category'] >= 0 && (empty($_REQUEST['cat_categories']) || count($_REQUEST['cat_categories']) <= 0)) {
                $category_needed = true;
                $smarty->assign('category_needed', 'y');
            } else {
                $category_needed = false;
            }
            if (isset($_REQUEST["save"]) && ! $category_needed && ! $contribution_needed) {
                if (strtolower($pagename) !== 'sandbox' || $tiki_p_admin === 'y') {
                    $description = TikiFilter::get('striptags')->filter($description);
                    if ($tikilib->page_exists($pagename)) {
                        if ($prefs['feature_multilingual'] === 'y') {
                            $info = $tikilib->get_page_info($pagename);
                            if ($info['lang'] !== $pageLang) {
                                $multilinguallib = TikiLib::lib('multilingual');
                                if ($multilinguallib->updateObjectLang('wiki page', $info['page_id'], $pageLang, true)) {
                                    $pageLang = $info['lang'];
                                    $smarty->assign('msg', tra("The language can't be changed as its set of translations has already this language"));
                                    $smarty->display("error.tpl");
                                    die;
                                }
                            }
                        }

                        $tikilib->update_page($pagename, $part["body"], tra('page imported'), $author, $authorid, $description, 0, $pageLang, false, $hash);
                    } else {
                        $tikilib->create_page($pagename, $hits, $part["body"], $lastmodified, tra('created from import'), $author, $authorid, $description, $pageLang, false, $hash);
                    }

                    // Handle the translation bits after actual creation/update
                    // This path is never used by minor updates
                    if ($prefs['feature_multilingual'] === 'y') {
                        $multilinguallib = TikiLib::lib('multilingual');
                        $tikilib->cache_page_info = [];

                        if ($editlib->isNewTranslationMode()) {
                            if ($editlib->aTranslationWasSavedAs('complete')) {
                                $editlib->saveCompleteTranslation();
                            } elseif ($editlib->aTranslationWasSavedAs('partial')) {
                                $editlib->savePartialTranslation();
                            }
                        } elseif ($editlib->isUpdateTranslationMode()) {
                            if ($editlib->aTranslationWasSavedAs('complete')) {
                                $editlib->saveCompleteTranslation();
                            } elseif ($editlib->aTranslationWasSavedAs('partial')) {
                                $editlib->savePartialTranslation();
                            }
                        } else {
                            $info = $tikilib->get_page_info($pagename);
                            $flags = [];
                            if (isset($_REQUEST['translation_critical'])) {
                                $flags[] = 'critical';
                            }
                            $multilinguallib->createTranslationBit('wiki page', $info['page_id'], $info['version'], $flags);
                        }
                    }
                }
            } else {
                $_REQUEST["edit"] = $last_part;
            }
        }
    }

    // If the watch state is not the same
    if ($requestedWatch !== $currentlyWatching) {
        if ($requestedWatch) {
            $tikilib->add_user_watch($user, 'wiki_page_changed', $page, 'wiki page', $page, $wikilib->sefurl($page));
        } else {
            $tikilib->remove_user_watch($user, 'wiki_page_changed', $page, 'wiki page');
        }
    }

    if (isset($_REQUEST["save"])) {                 // jb tiki 6 - this block of code seems to be redundant and unused - TOKIL
        unset($_REQUEST["save"]);
        if ($page_ref_id) {
            $url = "tiki-index.php?page_ref_id=$page_ref_id";
        } else {
            $url = $wikilib->sefurl($page);
        }
        if ($prefs['feature_best_language'] === 'y') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'no_bl=y';
        }


        if ($prefs['flaggedrev_approval'] == 'y' && $tiki_p_wiki_approve == 'y') {
            $flaggedrevisionlib = TikiLib::lib('flaggedrevision');

            if ($flaggedrevisionlib->page_requires_approval($page)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'latest=1';
            }
        }
        if ($dieInsteadOfForwardingWithHeader) {
            die("-- tiki-editpage: Dying before second call to header(), so we can see traces. Forwarding to: '$url'");
        }
        $access->redirect($url);
    }
}

$smarty->assign('category_needed', $category_needed);
$smarty->assign('contribution_needed', $contribution_needed);
$wiki_up = DEPRECATED_IMG_WIKI_UP_PATH;
if ($tikidomain) {
    $wiki_up .= "/$tikidomain";
}
// Upload pictures here
if (($prefs['feature_wiki_pictures'] === 'y') && (isset($tiki_p_upload_picture)) && ($tiki_p_upload_picture === 'y')) {
    $i = 1;
    while (isset($_FILES['picfile' . $i])) {
        if (is_uploaded_file($_FILES['picfile' . $i]['tmp_name'])) {
            $picname = str_replace(' ', '', $_FILES['picfile' . $i]['name']);
            if (preg_match('/\.(gif|png|jpe?g)$/i', $picname)) {
                if (@getimagesize($_FILES['picfile' . $i]['tmp_name'])) {
                    $filegallib = TikiLib::lib('filegal');
                    try {
                        $filegallib->assertUploadedFileIsSafe($_FILES['picfile' . $i]['tmp_name'], $_FILES['picfile' . $i]['name']);
                    } catch (Exception $e) {
                        // This is never being reached, because right now getimagesize fails for
                        // any unsafe file (SVG). Doesn't hurt, as support is already done for future
                        // safety issues.
                        $smarty->assign('msg', $e->getMessage());
                        $smarty->display("error.tpl");
                        die();
                    }
                    move_uploaded_file($_FILES['picfile' . $i]['tmp_name'], "$wiki_up/$picname");
                    chmod("$wiki_up/$picname", 0644); // seems necessary on some system (see move_uploaded_file doc on php.net)
                }
            }
        }
        $i++;
    }
}
if ($prefs['feature_wiki_attachments'] === 'y' && isset($_REQUEST["attach"]) && ($tiki_p_wiki_attach_files === 'y' || $tiki_p_wiki_admin_attachments === 'y')) {
    if ($prefs['feature_use_fgal_for_wiki_attachments'] != 'y') {
        if (isset($_FILES['userfile2']) && is_uploaded_file($_FILES['userfile2']['tmp_name'])) {
            $ret = $tikilib->attach_file($_FILES['userfile2']['name'], $_FILES['userfile2']['tmp_name'], $prefs['w_use_db'] === 'y' ? 'db' : 'dir');
            if ($ret['ok']) {
                $wikilib->wiki_attach_file($page, $_FILES['userfile2']['name'], $_FILES['userfile2']['type'], $_FILES['userfile2']['size'], ($prefs['w_use_db'] === 'dir') ? '' : $ret['data'], $_REQUEST["attach_comment"], $user, $ret['fhash']);
            } else {
                    $smarty->assign('msg', $ret['error']);
                    $smarty->display("error.tpl");
                    die();
            }
        }
    } else {
        if (isset($_FILES['userfile']) && isset($_FILES['userfile']['tmp_name'][0]) && is_uploaded_file($_FILES['userfile']['tmp_name'][0])) {
            $filegallib = TikiLib::lib('filegal');
            $galleryId = $filegallib->get_attachment_gallery($page, 'wiki page', true);
            $uploads = $filegallib->actionHandler(
                'uploadFile',
                [
                    'galleryId' => [$galleryId],
                    'comment' => [$_REQUEST["attach_comment"]],
                    'returnTransfer' => true,
                ]
            );
            if ($uploads) {
                if (isset($_REQUEST['edit'])) {
                    foreach ($uploads as $upload) {
                        $_REQUEST['edit'] .= '[' . smarty_modifier_sefurl($upload['fileId'], 'file') . '|' . $upload['name'] . ']';
                    }
                }
                Feedback::success(tr('File uploaded'));
            }
        }
    }
}


// Suck another page and append to the end of current
$suck_url = isset($_REQUEST["suck_url"]) ? $_REQUEST["suck_url"] : '';

if (isset($_REQUEST["parsehtml"])) {
    $parsehtml = $_REQUEST["parsehtml"] === 'on' ? 'y' : 'n';
} else {
    // FIXME: If the user hasn't checked, we attempt to unparse anyway if ! is_html. Better not display the checkbox than ignoring its value
    $parsehtml = isset($info['is_html']) ? ($info['is_html'] ? 'n' : 'y') : 'n';
}
$smarty->assign('parsehtml', $parsehtml);

if (isset($_REQUEST['do_suck']) && strlen($suck_url) > 0) {
    // \note by zaufi
    //   This is ugly implementation of wiki HTML import.
    //   I think it should be plugable import/export converters with ability
    //   to choose from edit form what converter to use for operation.
    //   In case of import converter, it can try to guess what source
    //   file is (using mime type from remote server response).
    //   Of couse converters may have itsown configuration panel what should be
    //   pluged into wiki page edit form too... (like HTML importer may have
    //   flags 'strip HTML tags' and 'try to convert HTML to wiki' :)
    //   At least one export filter for wiki already coded :) -- PDF exporter...
    $sdta = $tikilib->httprequest($suck_url);
    if (error_get_last()) {
        $smarty->assign('msg', tra("Can't import remote HTML page"));
        $smarty->display("error.tpl");
        die;
    }
    // Need to parse HTML?
    if ($parsehtml === 'y') {
        $sdta = $editlib->parse_html($sdta);
    }
    $_REQUEST['edit'] = $jitRequest['edit'] . $sdta;
}
// if "UserPage" complete with the user name
if ($prefs['feature_wiki_userpage'] === 'y' && $tiki_p_admin !== 'y' && $page === $prefs['feature_wiki_userpage_prefix']) {
    $page .= $user;
    $_REQUEST['page'] = $page;
}

if (strtolower($_REQUEST["page"]) === 'sandbox' && $prefs['feature_sandbox'] !== 'y') {
    $smarty->assign('msg', tra("The Sandbox is disabled"));
    $smarty->display("error.tpl");
    die;
}

if (! isset($_REQUEST["comment"])) {
    $_REQUEST["comment"] = '';
}

// Get page data
if (isset($info['wiki_cache'])) {
    $prefs['wiki_cache'] = $info['wiki_cache'];
    $smarty->assign('wiki_cache', $prefs['wiki_cache']);
}

if (isset($info["flag"]) ? $info["flag"] === 'L' : false && ! $wikilib->is_editable($page, $user, $info)) {
    $smarty->assign('msg', tra("The page cannot be edited because it is locked"));
    $smarty->display("error.tpl");
    die;
}

$smarty->assign('editable', 'y');
$smarty->assign('show_page', 'n');
$smarty->assign('comments_show', 'n');

$smarty->assign_by_ref('data', $info);
$smarty->assign('footnote', '');
$smarty->assign('has_footnote', 'n');
$parserlib = TikiLib::lib('parser');
if ($prefs['feature_wiki_footnotes'] === 'y') {
    if ($user) {
        $x = $wikilib->get_footnote($user, $page);
        $footnote = $wikilib->get_footnote($user, $page);
        $smarty->assign('footnote', $footnote);
        if ($footnote) {
            $smarty->assign('has_footnote', 'y');
        }
        $smarty->assign('parsed_footnote', $parserlib->parse_data($footnote));
        if (isset($_REQUEST['footnote'])) {
            $access->checkCsrf();
            $smarty->assign('parsed_footnote', $parserlib->parse_data($_REQUEST['footnote']));
            $smarty->assign('footnote', $_REQUEST['footnote']);
            $smarty->assign('has_footnote', 'y');
            if (empty($_REQUEST['footnote'])) {
                $wikilib->remove_footnote($user, $page);
            } else {
                $wikilib->replace_footnote($user, $page, $_REQUEST['footnote']);
            }
        }
    }
}
if (isset($_REQUEST["customTip"]) && ! isset($_REQUEST['preview']) && ! isset($_REQUEST['save'])) {
    $smarty->assign('customTip', $_REQUEST['customTip']);
    if (isset($_REQUEST["customTipTitle"])) {
        $smarty->assign('customTipTitle', tra($_REQUEST["customTipTitle"]));
    } else {
        $smarty->assign('customTipTitle', tra('Tip'));
    }
}
if (isset($_REQUEST["wikiHeaderTpl"]) && ! isset($_REQUEST['preview']) && ! isset($_REQUEST['save'])) {
    $smarty->assign('wikiHeaderTpl', $smarty->fetch("wiki:{$_REQUEST['wikiHeaderTpl']}"));
}
if ((isset($_REQUEST["template_name"]) || isset($_REQUEST["templateId"])) && ! isset($_REQUEST['preview']) && ! isset($_REQUEST['save'])) {
    $templateLang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;

    if (isset($_REQUEST["templateId"])) {
        $templateId = $_REQUEST["templateId"];
    } else {
        $multilinguallib = TikiLib::lib('multilingual');
        $templateId = $multilinguallib->getTemplateIDInLanguage('wiki', $_REQUEST["template_name"], $templateLang);
    }
    $template_data = TikiLib::lib('template')->get_template($templateId, $templateLang);
    $_REQUEST["edit"] = $template_data["content"] . "\n" . $_REQUEST["edit"];
    $smarty->assign("templateId", $templateId);
}

if (isset($_REQUEST["ratingId"]) && $_REQUEST["ratingId"] > 0) {
    $smarty->assign("poll_template", $_REQUEST["ratingId"]);
} else {
    $smarty->assign("poll_template", 0);
}

if (isset($_REQUEST["edit"])) {
    $edit_data = $_REQUEST["edit"];
} else {
    if (isset($info['draft'])) {
        $edit_data = $info['draft']['data'];
    } elseif (isset($info["data"])) {
        if ((isset($_REQUEST['hdr']) || (! empty($_REQUEST['pos']) && isset($_REQUEST['cell']))) && $prefs['wiki_edit_section'] === 'y') {
            if (isset($_REQUEST['hdr'])) {
                if ($_REQUEST['hdr'] === "0") {
                    list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], 1);
                    $real_len = $real_start;
                    $real_start = 0;
                } else {
                    list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], $_REQUEST['hdr']);
                }
            } else {
                include_once('lib/wiki-plugins/wikiplugin_split.php');
                list($real_start, $real_len) = wikiplugin_split_cell($info['data'], $_REQUEST['pos'], $_REQUEST['cell']);
            }
            $edit_data = substr($info['data'], $real_start, $real_len);
        } else {
            $edit_data = $info['data'];
        }
    } else {
        $edit_data = '';
    }
}

$likepages = '';
$smarty->assign_by_ref('likepages', $likepages);
if ($prefs['feature_likePages'] === 'y' and $edit_data === '' && ! $tikilib->page_exists($page)) {
    $likepages = $wikilib->get_like_pages($page);
}

if (isset($prefs['wiki_feature_copyrights']) && $prefs['wiki_feature_copyrights'] === 'y') {
    if (isset($_REQUEST['copyrightTitle'])) {
        $smarty->assign('copyrightTitle', $_REQUEST["copyrightTitle"]);
    }
    if (isset($_REQUEST['copyrightYear'])) {
        $smarty->assign('copyrightYear', $_REQUEST["copyrightYear"]);
    }
    if (isset($_REQUEST['copyrightAuthors'])) {
        $smarty->assign('copyrightAuthors', $_REQUEST["copyrightAuthors"]);
    }
    if (isset($_REQUEST['copyrightHolder'])) {
        $smarty->assign('copyrightHolder', $_REQUEST["copyrightHolder"]);
    }
}

/* Local reference handling */
if (isset($prefs['feature_references']) && $prefs['feature_references'] === 'y') {
    if ($prefs['wikiplugin_addreference'] == 'y') {
        $referenceslib = TikiLib::lib('references');
        $page_id = TikiLib::lib('tiki')->get_page_id_from_name($page);
        if ($page_id) {
            $smarty->assign('showBiblioSection', '1');

            $references = $referenceslib->list_references($page_id);
            $lib_references = $referenceslib->list_lib_references();
            $perms = Perms::get(['wiki page', $page]);

            if ($perms->use_references) {
                $use_references = 1;
            } else {
                $use_references = 0;
            }

            if ($perms->edit_references) {
                $edit_references = 1;
            } else {
                $edit_references = 0;
            }

            $assoc_references = $referenceslib->list_assoc_references($page_id);

            $page_info = TikiLib::lib('tiki')->get_page_info($page);
            $listOfCodes = \Tiki\WikiPlugin\Reference::extractBibliographicCodesFromText($page_info['data']);
            $listOfCodes = array_unique($listOfCodes);

            $key_exists = [];
            foreach ($listOfCodes as $m) {
                if (array_key_exists($m, $assoc_references['data'])) {
                    $key_exists[$m] = 1;
                }
            }
            foreach ($references['data'] as $key => $ref) {
                if (array_key_exists($ref['biblio_code'], $key_exists)) {
                    $references['data'][$key]['is_present'] = 1;
                } else {
                    $references['data'][$key]['is_present'] = 0;
                }
            }

            $smarty->assign('key_exists', $key_exists);
            $smarty->assign('referencesCant', $references['cant']);
            $smarty->assign('references', $references['data']);

            if ($references['cant'] < 1 && $lib_references['cant'] < 1) {
                $smarty->assign('display', 'none');
            } else {
                $smarty->assign('display', 'block');
            }
            $smarty->assign('ajaxURL', $GLOBALS['base_url']);

            $smarty->assign('libReferencesCant', $lib_references['cant']);
            $smarty->assign('libReferences', $lib_references['data']);
            $smarty->assign('use_references', $use_references);
            $smarty->assign('edit_references', $edit_references);
        } else {
            $smarty->assign('showBiblioSection', '0');
        }
    }
}
/* Local reference handling */


if (isset($_REQUEST["comment"])) {
    $smarty->assign_by_ref('commentdata', $_REQUEST["comment"]);
} elseif (isset($info['draft'])) {
    $smarty->assign_by_ref('commentdata', $info['draft']['data']);
} else {
    $smarty->assign('commentdata', '');
}
if (isset($info["description"])) {
    if (isset($info['draft'])) {
        $info['description'] = $info['draft']['description'];
    }
    $smarty->assign('description', $info["description"]);
    $description = $info["description"];
} else {
    $smarty->assign('description', '');
    $description = '';
}
if (isset($_REQUEST["description"])) {
    $smarty->assign_by_ref('description', $_REQUEST["description"]);
    $description = $_REQUEST["description"];
}
if ($prefs['wiki_customize_title_tag'] === 'y') {
    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->get_attribute('wiki page', $page, "tiki.wiki.page_title");
    if ($attributes !== false) {
        $smarty->assign('tagTitle', $attributes);
    } else {
        $smarty->assign('tagTitle', '');
    }
}
if ($prefs['sitemap_enable'] === 'y') {
    $attributelib = TikiLib::lib('attribute');
    $attributes = $attributelib->get_attribute('wiki page', $page, "tiki.object.sitemap");
    if ($attributes !== false) {
        $smarty->assign('for_sitemap', $attributes);
    } else {
        $smarty->assign('for_sitemap', 'y');
    }
}
$wiki_authors_style = '';
if ($prefs['wiki_authors_style_by_page'] === 'y') {
    if (isset($_REQUEST['wiki_authors_style']) && $tiki_p_admin_wiki === 'y') {
        $wiki_authors_style = $_REQUEST['wiki_authors_style'];
    } elseif (! empty($info['wiki_authors_style'])) {
        $wiki_authors_style = $info['wiki_authors_style'];
    }
    $smarty->assign('wiki_authors_style', $wiki_authors_style);
}

if ($is_html) {
    $smarty->assign('allowhtml', 'y');
} else {
    if (! empty($_REQUEST['preview'])) {
        $edit_data = str_replace('<x>', '', $edit_data);
    }
    $smarty->assign('allowhtml', 'n');
}
if (empty($_REQUEST['lock_it']) && ! empty($info['flag']) && $info['flag'] === 'L') {
    $lock_it = 'y';
}
$smarty->assign_by_ref('lock_it', $lock_it);
if ($prefs['wiki_comments_allow_per_page'] !== 'n') {
    if (! isset($_REQUEST['save']) && ! isset($_REQUEST['preview'])) {
        if (! empty($info) && ! empty($info['comments_enabled'])) {
            $comments_enabled = $info['comments_enabled'];
        } else {
            if ($prefs['wiki_comments_allow_per_page'] === 'y') {
                $comments_enabled = 'y';
            } else {
                $comments_enabled = 'n';
            }
        }
    }
    $smarty->assign_by_ref('comments_enabled', $comments_enabled);
}
if (isset($_REQUEST["lang"])) {
    if ($prefs['feature_multilingual'] === 'y' && isset($info["lang"]) && $info['lang'] !== $_REQUEST["lang"]) {
        $multilinguallib = TikiLib::lib('multilingual');
        if ($multilinguallib->updateObjectLang('wiki page', $info['page_id'], $_REQUEST["lang"], true)) {
            $pageLang = $info['lang'];
            $smarty->assign('msg', tra("The language can't be changed as its set of translations has already this language"));
            $smarty->display("error.tpl");
            die;
        }
    }
    $pageLang = $_REQUEST["lang"];
} elseif (isset($info["lang"])) {
    $pageLang = $info["lang"];
} elseif (isset($edit_lang)) {
    $pageLang = $edit_lang;
} else {
    $pageLang = "";
}

$smarty->assign('lang', $pageLang);
if ($prefs['feature_urgent_translation'] === 'y') {
    $urgent_allowed = true;
    $smarty->assign('urgent_allowed', $urgent_allowed);
}
if (isset($_REQUEST['translation_critical'])) {
    $smarty->assign('translation_critical', 1);
} else {
    $smarty->assign('translation_critical', 0);
}

// Parse (or not) $edit_data into $parsed
// Handles switching editor modes
if (! isset($_REQUEST['preview']) && ! isset($_REQUEST['save'])) {
    if (isset($_REQUEST['mode_normal']) && $_REQUEST['mode_normal'] === 'y') {
        // Convert page content, as we are switching from the WYSIWYG editor to the regular editor
        if (! $is_html) {
            // we come from WYSIWYG-Wiki
            $parsed = $edit_data;
        } else {
            // we come from WYSIWYG-HTML
            $parsed = $editlib->parseToWiki($edit_data);
        }
        $is_html = false;
        $info['is_html'] = false;
        $info['wysiwyg'] = false;
        $smarty->assign('allowhtml', 'n');
    } elseif (isset($_REQUEST['mode_wysiwyg']) && $_REQUEST['mode_wysiwyg'] === 'y') {
        // We are switching from the regular editor to the WYSIWYG editor
        if (! $is_html && $prefs['wysiwyg_htmltowiki'] === 'y') {
            // we switch to WYSIWYG-Wiki
            $parsed = $edit_data;
        } else {
            // we switch to WYSIWYG-HTML (regular WYSIWYG)
            $parsed = $editlib->parseToWysiwyg($edit_data, true); // Convert page content
            $is_html = true;
            $smarty->assign('allowhtml', 'y');
        }
        $info['is_html'] = $is_html;
        $info['wysiwyg'] = true;
    } elseif ($_SESSION['wysiwyg'] === 'y') {
        if (empty($info['is_html'])) {
            if ($edit_data == 'ajax error') {
                unset($_REQUEST['save']);   // don't save an ajax error
            }
        } else {
            $parsed = $parserlib->parse_data(
                $edit_data,
                [
                    'absolute_links' => true,
                    'noheaderinc' => true,
                    'suppress_icons' => true,
                    'wysiwyg' => true,
                    'is_html' => true,
                    'process_wiki_paragraphs' => false
                 ]
            );
        }
    }
    if (isset($info['pageName'])) {
        $tikilib->check_duplicate_alias($edit_data, $info['pageName']);
    }
}

if (empty($parsed)) {
        $parsed = $edit_data;
}
$smarty->assign('pagedata', $parsed);

// apply the optional post edit filters before preview
if (isset($_REQUEST["preview"])) {
    if ($_SESSION['wysiwyg'] === 'y' && $prefs['wysiwyg_wiki_parsed'] === 'y') {
        $parsed = $editlib->partialParseWysiwygToWiki($parsed);
        $parsed = $parserlib->parse_data($parsed, ['absolute_links' => true, 'noheaderinc' => true, 'suppress_icons' => true, 'preview_mode' => true, 'is_html' => $is_html]);
    } else {
        $parsed = $parserlib->parse_data($parsed, ['is_html' => $is_html, 'preview_mode' => true]);
    }
    // If we are in preview mode then preview it!
    $smarty->assign('preview', 1);
} else {
    $parsed = '';
    $smarty->assign('preview', 0);
}

$smarty->assign_by_ref('parsed', $parsed);

/**
 * @param $obj
 * @param $parts
 * @param $i
 */
function parse_output(&$obj, &$parts, $i)
{
    if (! empty($obj['parts'])) {
        foreach ($obj['parts'] as $index => $part) {
            parse_output($part, $parts, $index);
        }
    } elseif ($obj['type'] === 'application/x-tikiwiki') {
        $aux["body"] = $obj['body'];
        $ccc = $obj['header']["content-type"];
        $items = explode(';', $ccc);
        foreach ($items as $item) {
            $portions = explode('=', $item);
            if (isset($portions[0]) && isset($portions[1])) {
                $aux[trim($portions[0])] = trim($portions[1]);
            }
        }
        $parts[] = $aux;
    }
}
// Pro
// Check if the page has changed
$pageAlias = '';
$cat_type = 'wiki page';
$cat_objid = $_REQUEST["page"];
if (
    isset($_REQUEST['save'])
     && $prefs['feature_contribution'] === 'y'
     && $prefs['feature_contribution_mandatory'] === 'y'
     && (empty($_REQUEST['contributions']) || count($_REQUEST['contributions']) <= 0)
) {
    $contribution_needed = true;
    $smarty->assign('contribution_needed', 'y');
} else {
    $contribution_needed = false;
}
if (
    isset($_REQUEST['save'])
    && $prefs['feature_categories'] === 'y'
    && $prefs['feature_wiki_mandatory_category'] >= 0
    && (empty($_REQUEST['cat_categories']) || count($_REQUEST['cat_categories']) <= 0)
) {
    $category_needed = true;
    $smarty->assign('category_needed', 'y');
} else {
    $category_needed = false;
}
if (isset($_REQUEST['save']) && $prefs['wiki_mandatory_edit_summary'] === 'y' && empty($_REQUEST['comment'])) {
    $summary_needed = true;
    $smarty->assign('summary_needed', 'y');
} else {
    $summary_needed = false;
}
if ($prefs['wiki_mandatory_edit_summary'] === 'y') {
    $headerlib->add_jq_onready(
        '$("input[name=save],input[name=minor]").on("click", function(){
            if (!$("#comment").val()) {
                var s = prompt("' . tra('Describe the change you made') . '", "");
                if (!s) return false;
                $("#comment").val(s);
            }
            return true;
        })'
    );
}

if ($prefs['site_layout_per_object'] == 'y') {
    $attributelib = TikiLib::lib('attribute');

    if (isset($jitPost['object_layout'])) {
        $attributelib->set_attribute('wiki page', $page, 'tiki.object.layout', $jitPost->object_layout->word());
    }

    $attributes = $attributelib->get_attributes('wiki page', $page);
    $smarty->assign('object_layout', [
        'available' => TikiLib::lib('theme')::listUserSelectableLayouts(),
        'current' => isset($attributes['tiki.object.layout']) ? $attributes['tiki.object.layout'] : null,
    ]);
}

if (
    isset($_REQUEST["save"])
        && (strtolower($_REQUEST['page']) !== 'sandbox' || $tiki_p_admin === 'y')
        && ! $category_needed
        && ! $contribution_needed
        && ! $summary_needed
) {
    if (strtolower($_REQUEST["page"]) == 'sandbox' && $prefs['feature_sandbox'] === 'y') {
        $smarty->assign('msg', tra("The Sandbox page cannot be saved because Sandbox is a page where you can practice editing etc. and use the preview feature to check how the page looks. No version is stored for this page."));
        $smarty->display("error.tpl");
        die;
    } else {
       // $access->checkCsrf();
        // Check if all Request values are delivered, and if not, set them
        // to avoid error messages. This can happen if some features are
        // disabled
        if (! isset($_REQUEST["description"])) {
            $_REQUEST["description"] = '';
        }
        if (! isset($_REQUEST["wiki_authors_style"])) {
            $_REQUEST["wiki_authors_style"] = '';
        }
        if (! isset($_REQUEST["comment"])) {
            $_REQUEST["comment"] = '';
        }
        if (! isset($_REQUEST["lang"])) {
            $_REQUEST["lang"] = '';
        }
        if (! isset($_REQUEST['wysiwyg'])) {
            $_REQUEST['wysiwyg'] = 'n';
        }
        if (isset($_REQUEST['wiki_cache'])) {
            $wikilib->set_page_cache($_REQUEST['page'], $_REQUEST['wiki_cache']);
        }
        $cat_desc = ($prefs['feature_wiki_description'] === 'y') ? substr($_REQUEST["description"], 0, 200) : '';
        $cat_name = $_REQUEST["page"];
        $cat_href = "tiki-index.php?page=" . urlencode($cat_objid);
        $cat_lang = $_REQUEST['lang'];

        $page = $_REQUEST["page"];

        $edit = $_REQUEST["edit"];

        // convert absolute to relative links
        $edit = $tikilib->convertAbsoluteLinksToRelative($edit);

        // TODO ImageGalleryRemoval23.x replace with a file gallery version
        // Parse $edit and eliminate image references to external URIs (make them internal)
        //$edit = $imagegallib->capture_images($edit);

        // add permisions here otherwise return error!
        if (
            isset($prefs['wiki_feature_copyrights'])
            && $prefs['wiki_feature_copyrights'] === 'y'
            && isset($_REQUEST['copyrightTitle'])
            && isset($_REQUEST['copyrightYear'])
            && isset($_REQUEST['copyrightAuthors'])
            && isset($_REQUEST['copyrightHolder'])
            && ! empty($_REQUEST['copyrightYear'])
            && ! empty($_REQUEST['copyrightTitle'])
        ) {
            include_once("lib/copyrights/copyrightslib.php");
            $copyrightslib = new CopyrightsLib();
            $copyrightYear = $_REQUEST['copyrightYear'];
            $copyrightTitle = $_REQUEST['copyrightTitle'];
            $copyrightAuthors = $_REQUEST['copyrightAuthors'];
            $copyrightHolder = $_REQUEST['copyrightHolder'];
            $copyrightslib->add_copyright($page, $copyrightTitle, $copyrightYear, $copyrightAuthors, $copyrightHolder, $user);
        }

        $exist = $tikilib->page_exists($_REQUEST['page']);
        // If page exists
        if (! $exist) {
            // Extract links and update the page
            $links = $tikilib->get_links($_REQUEST["edit"]);
            /*
            $notcachedlinks = $tikilib->get_links_nocache($_REQUEST["edit"]);
            $cachedlinks = array_diff($links, $notcachedlinks);
            $tikilib->cache_links($cachedlinks);
            */

            $info_old = null;

            $tikilib->create_page(
                $_REQUEST["page"],
                0,
                $edit,
                $tikilib->now,
                $_REQUEST["comment"],
                $user,
                $tikilib->get_ip_address(),
                $description,
                $pageLang,
                $is_html,
                $hash,
                $_REQUEST['wysiwyg'],
                $wiki_authors_style,
                0,
                ''
            );

            $info_new = $tikilib->get_page_info($page);

            if ($editlib->isNewTranslationMode() && ! empty($pageLang)) {
                $multilinguallib = TikiLib::lib('multilingual');
                $infoSource = $tikilib->get_page_info($editlib->sourcePageName);
                $infoCurrent = $tikilib->get_page_info($editlib->targetPageName);
                if ($multilinguallib->insertTranslation('wiki page', $infoSource['page_id'], $infoSource['lang'], $infoCurrent['page_id'], $pageLang)) {
                    $pageLang = $info['lang'];
                    $smarty->assign('msg', tra("The language can't be changed as its set of translations has already this language"));
                    $smarty->display("error.tpl");
                    die;
                }
            }

            if ($prefs['feature_multilingual'] === 'y') {
                $multilinguallib = TikiLib::lib('multilingual');

                $tikilib->cache_page_info = [];
                if ($editlib->isNewTranslationMode()) {
                    if ($editlib->aTranslationWasSavedAs('complete')) {
                        $editlib->saveCompleteTranslation();
                    } elseif ($editlib->aTranslationWasSavedAs('partial')) {
                        $editlib->savePartialTranslation();
                    }
                } else {
                    $info = $tikilib->get_page_info($_REQUEST['page']);
                    $multilinguallib->createTranslationBit('wiki page', $info['page_id'], 1);
                }
            }
        } else {
            $links = $tikilib->get_links($edit);
            /*
            $tikilib->cache_links($links);
            */
            $minor = (isset($_REQUEST['isminor']) && $_REQUEST['isminor'] === 'on') ? 1 : 0;

            if ((isset($_REQUEST['hdr']) || (! empty($_REQUEST['pos']) && isset($_REQUEST['cell']))) && $prefs['wiki_edit_section'] === 'y') {
                if (isset($_REQUEST['hdr'])) {
                    if ($_REQUEST['hdr'] == 0) {
                        list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], 1);
                        $real_len = $real_start;
                        $real_start = 0;
                    } else {
                        list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], $_REQUEST['hdr']);
                    }
                } else {
                    include_once('lib/wiki-plugins/wikiplugin_split.php');
                    list($real_start, $real_len) = wikiplugin_split_cell($info['data'], $_REQUEST['pos'], $_REQUEST['cell']);
                }
                if ($edit[strlen($edit) - 1] !== "\n") {
                    $edit .= "\r\n";
                }
                $edit = substr($info['data'], 0, $real_start) . $edit . substr($info['data'], $real_start + $real_len);
            }

            if ($_SESSION['wysiwyg'] === 'y' && $prefs['wysiwyg_wiki_parsed'] === 'y') {
                $edit = $editlib->partialParseWysiwygToWiki($edit);
            }

            $info_old = $tikilib->get_page_info($page);

            $tikilib->update_page(
                $_REQUEST["page"],
                $edit,
                $_REQUEST["comment"],
                $user,
                $tikilib->get_ip_address(),
                $description,
                $minor,
                $pageLang,
                isset($_REQUEST['allowhtml']) ? $_REQUEST['allowhtml'] : $is_html,
                $hash,
                null,
                $_REQUEST['wysiwyg'],
                $wiki_authors_style,
                false
            );
            $info_new = $tikilib->get_page_info($page);
            if (isset($_REQUEST['content_title'])) {
                $attributelib = TikiLib::lib('attribute');
                $attributelib->set_attribute('wiki page', $page, 'tiki.wiki.page_title', $_REQUEST['content_title']);
            }
            if (isset($_REQUEST['for_sitemap'])) {
                $attributelib = TikiLib::lib('attribute');
                $attributelib->set_attribute('wiki page', $page, 'tiki.object.sitemap', $_REQUEST['for_sitemap']);
            }

            // Handle translation bits
            if ($prefs['feature_multilingual'] === 'y' && ! $minor) {
                $multilinguallib = TikiLib::lib('multilingual');
                $tikilib->cache_page_info = [];

                if ($editlib->isUpdateTranslationMode()) {
                    if ($editlib->aTranslationWasSavedAs('complete')) {
                        $editlib->saveCompleteTranslation();
                    } elseif ($editlib->aTranslationWasSavedAs('partial')) {
                        $editlib->savePartialTranslation();
                    }
                } else {
                    $info = $tikilib->get_page_info($_REQUEST['page']);
                    $flags = [];
                    if (isset($_REQUEST['translation_critical'])) {
                        $flags[] = 'critical';
                    }
                    $multilinguallib->createTranslationBit('wiki page', $info['page_id'], $info['version'], $flags);
                }
            }
        }

        if ($prefs['object_maintainers_enable'] === 'y') {
            $relationlib = TikiLib::lib('relation');
            $attributelib = TikiLib::lib('attribute');

            if (isset($info['pageName'])) {
                // We erase the preexisting relation either because there are no more maintainers,
                // or because there are maintainers and we want to make sure old maintainers that have been replaced get removed
                $relationlib -> remove_relations_from('wiki page', $info['pageName'], 'tiki.object.maintainer');

                if (! empty($_REQUEST["maintainers"])) {
                    $maintainers = explode(';', $_REQUEST["maintainers"]);
                    TikiLib::lib('object') -> set_maintainers($info['pageName'], $maintainers, 'wiki page');
                }

                if (! empty($_REQUEST["update_frequency"]) && $_REQUEST["update_frequency"] > 0) {
                    $attributelib -> set_attribute('wiki page', $info['pageName'], 'tiki.object.update_frequency', $_REQUEST["update_frequency"]);
                } else { // Erase potentially preexisting update frequency
                    $attributelib -> set_attribute('wiki page', $info['pageName'], 'tiki.object.update_frequency', ''); // param $value === '' means delete
                }
            }
        }

        include_once("categorize.php");
        include_once("poll_categorize.php");

        /*RP NOTE: freetabs was causing larger site's pages to be lost when editing sections
        * this has to do with lucene indexing being refreshed.
        * this was traced to lib/core/Search/Index -> getGlobalContent();
        * This seems to divert it when sections are being edited, please either fix or leave
        * */
        if (! isset($_REQUEST['hdr'])) {
            include_once("freetag_apply.php");
        }

        //Page may have been inserted from a structure page view
        if (isset($_REQUEST['current_page_id'])) {
            $page_info = $structlib->s_get_page_info($_REQUEST['current_page_id']);
            $pageAlias = $page_info['page_alias'];
            if (isset($_REQUEST["add_child"])) {
                //Insert page after last child of current page
                $subpages = $structlib->s_get_pages($_REQUEST["current_page_id"]);
                $max = count($subpages);
                $last_child_ref_id = null;
                if ($max !== 0) {
                    $last_child = $subpages[$max - 1];
                    $last_child_ref_id = $last_child["page_ref_id"];
                }
                $page_ref_id = $structlib->s_create_page($_REQUEST['current_page_id'], $last_child_ref_id, $_REQUEST["page"], '', $page_info['structure_id']);
            } else {
                //Insert page after current page
                $page_ref_id = $structlib->s_create_page($page_info["parent_id"], $_REQUEST['current_page_id'], $_REQUEST["page"], '', $page_info['structure_id']);
            }
            // Inherit direct object permissions for pages added to a structure, if the user can edit the structure and the page
            if (! isset($prefs['feature_wiki_no_inherit_perms_structure']) || $prefs['feature_wiki_no_inherit_perms_structure'] === 'n') {
                if (
                    $tikilib->user_has_perm_on_object($user, $_REQUEST["page"], 'wiki page', 'tiki_p_edit_structures', 'tiki_p_edit') ||
                    ($tikilib->user_has_perm_on_object($user, $_REQUEST["page"], 'wiki page', 'tiki_p_admin_wiki'))
                ) {
                    $userlib->copy_object_permissions($page_info["pageName"], $_REQUEST["page"], 'wiki page');
                }
            }
        }

        /* Local reference handling */
        if (isset($prefs['feature_references']) && $prefs['feature_references'] === 'y') {
            if ($prefs['wikiplugin_addreference'] == 'y') {
                if (isset($_REQUEST['ref_biblio_code'])) {
                    $ref_biblio_code = $_REQUEST['ref_biblio_code'];
                    $ref_author = $_REQUEST['ref_author'];
                    $ref_title = $_REQUEST['ref_title'];
                    $ref_part = $_REQUEST['ref_part'];
                    $ref_uri = $_REQUEST['ref_uri'];
                    $ref_code = $_REQUEST['ref_code'];
                    $ref_year = $_REQUEST['ref_year'];
                    $ref_style = $_REQUEST['ref_style'];

                    if ($ref_biblio_code != '') {
                        $referenceslib = TikiLib::lib('references');
                        if (isset($info_new['page_id'])) {
                            $page_id = $info_new['page_id'];
                        } else {
                            $page_id = TikiLib::lib('tiki')->get_page_id_from_name($page);
                        }
                        $referenceslib->add_reference($page_id, $_REQUEST['ref_biblio_code'], $_REQUEST['ref_author'], $_REQUEST['ref_title'], $_REQUEST['ref_part'], $_REQUEST['ref_uri'], $_REQUEST['ref_code'], $_REQUEST['ref_year'], $_REQUEST['ref_style']);
                    }
                }
            }
        }
        /* Local reference handling */

        // If the watch state is not the same
        if ($requestedWatch !== $currentlyWatching) {
            if ($requestedWatch) {
                $tikilib->add_user_watch($user, 'wiki_page_changed', $page, 'wiki page', $page, $wikilib->sefurl($page));
            } else {
                $tikilib->remove_user_watch($user, 'wiki_page_changed', $page, 'wiki page');
            }
        }

        if (! empty($prefs['geo_locate_wiki']) && $prefs['geo_locate_wiki'] == 'y' && ! empty($_REQUEST['geolocation'])) {
            TikiLib::lib('geo')->set_coordinates('wiki page', $page, $_REQUEST['geolocation']);
        }

        if (isset($_REQUEST['pageAutoToc'])) {
            $isAutoTocActive = (int)$_REQUEST['pageAutoToc'];
            $isAutoTocActive = $isAutoTocActive == 0 ? null : $isAutoTocActive;
            $wikilib->set_page_auto_toc($page, $isAutoTocActive);
        }

        if ($prefs['wiki_page_hide_title'] == 'y' && isset($_REQUEST['page_hide_title'])) {
            $isHideTitle = (int)$_REQUEST['page_hide_title'];
            $isHideTitle = $isHideTitle == 0 ? null : $isHideTitle;
            $wikilib->set_page_hide_title($page, $isHideTitle);
        }

        if ($prefs['namespace_enabled'] == 'y' && isset($_REQUEST['explicit_namespace'])) {
            $wikilib->set_explicit_namespace($page, $_REQUEST['explicit_namespace']);
        }

        if (isset($_REQUEST['metatag_robotscustom'])) {
            $attribute = $_REQUEST['metatag_robotscustom'];
            $attribute = $attribute == "" ? null : $attribute;
            $wikilib->setPageMetatagRobotscustom($page, $attribute);
        }

        if (! empty($_REQUEST['returnto'])) {   // came from wikiplugin_include.php edit button
            if (isURL($_REQUEST['returnto'])) {
                $url = $_REQUEST['returnto'];
            } else {
                $url = $wikilib->sefurl($_REQUEST['returnto']);
            }
        } elseif ($page_ref_id) {
            //$url = "tiki-index.php?page_ref_id=$page_ref_id";
            $structure_info = $structlib->s_get_page_info($page_ref_id);
            if ($structure_info && $structure_info['parent_id']) {
                $structure_info = $structlib->s_get_page_info($structure_info['parent_id']);
            }
            if ($structure_info) {
                $url = smarty_function_sefurl([
                    'page' => $page,
                    'structure' => $structure_info['pageName'],
                    'page_ref_id' => $page_ref_id,
                ], $smarty->getEmptyInternalTemplate());
            } else {
                $url = $wikilib->sefurl($page);
            }
        } else {
            $url = $wikilib->sefurl($page);
        }
        if ($prefs['feature_multilingual'] === 'y' && $prefs['feature_best_language'] === 'y' && isset($info['lang']) && $info['lang'] !== $prefs['language']) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'no_bl=y';
        }

        if ($prefs['flaggedrev_approval'] == 'y' && $tiki_p_wiki_approve == 'y') {
            $flaggedrevisionlib = TikiLib::lib('flaggedrevision');

            if ($flaggedrevisionlib->page_requires_approval($page)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'latest=1';
            }
        }

        if ($info == false) {
            //This is a new page
            $info = [];
            $info['version'] = $prefs['feature_multilingual'] === 'y' ? 1 : 0;
        }

        if ($prefs['feature_multilingual'] !== 'y') {
            $info['version'] += 1; // FIXME: for some strange reason when multilingual is disabled it reports version - 1 so we adjust the version number here (if someone finds the culprit please fix there and remove the condition here)
        }

        if ($prefs['feature_history'] === 'y' && $tiki_p_wiki_view_history === 'y' && $info['version'] > 1) {
            $linktodiff = '<div class="linktodiff">' . tr('To review the changes you have just made %0compare the versions%1 in history of this page.', "<a class=\"alert-link\" href=\"tiki-pagehistory.php?page=" . urlencode($page) . "&newver=" . ($info['version']) . "&oldver=" . ($info['version'] - 1) . "\">", '</a>') . '</div>';
        } else {
            $linktodiff = '';
        }

        Feedback::success(tr('Page %0 saved (version %1).', $_REQUEST["page"], $info['version']) . $linktodiff);

        Feedback::showWatchers('wiki_page_changed', $page);

        if (! empty($_REQUEST['hdr'])) {
            $tmp = $parserlib->parse_data($edit);           // fills $anch[] so page refreshes at the section being edited
            $url .= "#" . $anch[$_REQUEST['hdr'] - 1]['id'];
        }

        if ($dieInsteadOfForwardingWithHeader) {
            die("-- tiki-editpage: Dying before third call to header(), so we can see traces. Forwarding to: '$url'");
        }
        $access->redirect($url);
    }
} //save
$smarty->assign('pageAlias', $pageAlias);
if ($prefs['feature_wiki_templates'] === 'y') {
    $templates = TikiLib::lib('template')->list_templates('wiki', 0, -1, 'name_asc', '');
    $smarty->assign_by_ref('templates', $templates["data"]);
}
if ($prefs['feature_polls'] === 'y' and $prefs['feature_wiki_ratings'] === 'y' && $tiki_p_wiki_admin_ratings === 'y') {
    $polllib = TikiLib::lib('poll');
    $categlib = TikiLib::lib('categ');
    if (isset($_REQUEST['removepoll'])) {
        $catObjectId = $categlib->is_categorized($cat_type, $cat_objid);
        $polllib->remove_object_poll($cat_type, $cat_objid, $_REQUEST['removepoll']);
    }
    $polls_templates = $polllib->get_polls('t');
    $smarty->assign('polls_templates', $polls_templates['data']);
    $poll_rated = $polllib->get_ratings($cat_type, $cat_objid);
    $smarty->assign('poll_rated', $poll_rated);
    if (isset($_REQUEST['poll_template'])) {
        $smarty->assign('poll_template', $_REQUEST['poll_template']);
    }
}

if ($prefs['feature_multilingual'] === 'y') {
    $languages = [];
    $langLib = TikiLib::lib('language');
    $languages = $langLib->list_languages();
    $smarty->assign_by_ref('languages', $languages);

    if ($editlib->isNewTranslationMode()) {
        $smarty->assign('translationOf', $editlib->sourcePageName);

        if ($tikilib->page_exists($page)) {
            // Display an error if the page already exists
            $smarty->assign(
                'msg',
                tra("That page already exists. Go back and choose a different name.") . "<P>" . tra("The page name is") . ": '$page'"
            );
            $smarty->display("error.tpl");
            die;
        }

        $multilinguallib = TikiLib::lib('multilingual');
        $sourceInfo = $tikilib->get_page_info($editlib->sourcePageName);
        if ($multilinguallib->getTranslation('wiki page', $sourceInfo['page_id'], $_REQUEST['lang'])) {
            // Display an error if the page already exists
            $smarty->assign('msg', tra("The translation set already contains a page in this language."));
            $smarty->display("error.tpl");
            die;
        }
    }
    $histlib = TikiLib::lib('hist');
    if ($editlib->isTranslationMode()) {
        histlib_helper_setup_diff(
            $editlib->sourcePageName,
            $editlib->oldSourceVersion,
            $editlib->newSourceVersion,
            $_REQUEST['diff_style']
        );
        $smarty->assign('diff_oldver', (int) $editlib->oldSourceVersion);
        $smarty->assign('diff_newver', (int) $editlib->newSourceVersion);
        $smarty->assign('update_translation', 'y');
    }
}
$cat_type = 'wiki page';
$cat_objid = $_REQUEST["page"];
$cat_lang = $pageLang;
$cat_object_exists = $tikilib->page_exists($_REQUEST['page']);
if (! $cat_object_exists) {
    $cookietab = 1;
}

$smarty->assign('section', $section);
include_once('tiki-section_options.php');
if ($prefs['feature_freetags'] === 'y') {
    include_once('freetag_list.php');
    // if given in the request, set the freetag list (used for preview mode, when coming back from zoom mode, ...)
    if (isset($_REQUEST['freetag_string'])) {
        $smarty->assign('taglist', $_REQUEST['freetag_string']);
    } elseif ($editlib->isNewTranslationMode()) {
        $tags = $freetaglib->get_all_tags_on_object_for_language($editlib->sourcePageName, 'wiki page', $pageLang);
        $smarty->assign('taglist', implode(' ', $tags));
    }
}
if ($prefs['feature_categories'] === 'y') {
    include_once("categorize_list.php");

    if (isset($_REQUEST["current_page_id"]) && $prefs['feature_wiki_categorize_structure'] === 'y' && $categlib->is_categorized('wiki page', $structure_info["pageName"])) {
        $categIds = $categlib->get_object_categories('wiki page', $structure_info["pageName"]);
        $smarty->assign('categIds', $categIds);
    } else {
        $smarty->assign('categIds', []);
    }
    if (isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], 'tiki-index.php') && ! $tikilib->page_exists($_REQUEST["page"])) { // default the categs the page you come from for a new page
        if (preg_match('/page=([^\&]+)/', $_SERVER['HTTP_REFERER'], $ms)) {
            $p = $ms[1];
        } else {
            $p = $wikilib->get_default_wiki_page();
        }
        $cs = $categlib->get_object_categories('wiki page', $p);
        for ($i = count($categories) - 1; $i >= 0; --$i) {
            if (in_array($categories[$i]['categId'], $cs)) {
                $categories[$i]['incat'] = 'y';
            }
        }
    }
}

if ($prefs['object_maintainers_enable'] === 'y') {
    if (isset($info['pageName'])) {
        $object_maintainers = TikiLib::lib('relation')->get_relations_from('wiki page', $info['pageName'], 'tiki.object.maintainer');
        if (! empty($object_maintainers)) {
            $maintainers = [];
            foreach ($object_maintainers as $object_maintainer) {
                $maintainers[] = $object_maintainer['itemId'];
            }
            $smarty->assign('object_maintainers', implode(';', $maintainers));
        }
        $update_frequency = TikiLib::lib('attribute')->get_attribute('wiki page', $info['pageName'], 'tiki.object.update_frequency');
        if (! empty($update_frequency)) {
            $smarty->assign('update_frequency', $update_frequency);
        } else {
            $smarty->assign('update_frequency', $prefs['object_maintainers_default_update_frequency']);
        }
    } else {
        $smarty->assign('update_frequency', $prefs['object_maintainers_default_update_frequency']);
    }
}

$page_name = $page;

if ($wikilib->contains_badchars($page) && ! $tikilib->page_exists($page)) {
    $smarty->assign('page_badchars_display', $wikilib->get_badchars());
}

$smarty->assign('showstructs', []);
if ($structlib->page_is_in_structure($_REQUEST["page"])) {
    $structs = $structlib->get_page_structures($_REQUEST["page"]);
    $smarty->assign('showstructs', $structs);
}
// Flag for 'page bar' that currently 'Edit' mode active
// so no need to show comments & attachments, but need
// to show 'wiki quick help'
$smarty->assign('edit_page', 'y');
if ($prefs['wiki_feature_copyrights'] === 'y' && $tiki_p_edit_copyrights === 'y') {
    include_once('lib/copyrights/copyrightslib.php');
    $copyrightslib = new CopyrightsLib();
    $copyrights = $copyrightslib->list_copyrights($_REQUEST["page"]);
    if ($copyrights['cant']) {
        $smarty->assign_by_ref('copyrights', $copyrights['data']);
    }
}
$defaultRows = $prefs['default_rows_textarea_wiki'];
if (! $user or $user === 'anonymous') {
    $smarty->assign('anon_user', 'y');
}
if ($prefs['feature_contribution'] === 'y') {
    include_once('contribution.php');
}

if (! empty($prefs['geo_locate_wiki']) && $prefs['geo_locate_wiki'] == 'y') {
    $smarty->assign('geolocation_string', TikiLib::lib('geo')->get_coordinates_string('wiki page', $page));
}

if ($prefs['feature_multilingual'] === 'y' && $tikilib->page_exists($page)) {
    $multilinguallib = TikiLib::lib('multilingual');
    $trads = $multilinguallib->getTranslations('wiki page', $info['page_id'], $page, $info['lang']);
    $smarty->assign('trads', $trads);
}

$smarty->assign('explicit_namespace', $wikilib->get_explicit_namespace($page));
$smarty->assign('pageAutoToc', $wikilib->get_page_auto_toc($page));
$smarty->assign('page_hide_title', $wikilib->get_page_hide_title($page));
$smarty->assign('metatag_robotscustom', $wikilib->getPageMetatagRobotscustom($page));

// setup properties tab visibility
if (
    ($prefs['feature_wiki_templates'] === 'y' && $tiki_p_use_content_templates === 'y') ||
    ($prefs['feature_wiki_usrlock'] === 'y' && ($tiki_p_lock === 'y' || $tiki_p_admin_wiki === 'y')) ||
    ($prefs['feature_wiki_replace'] === 'y' && $_SESSION['wysiwyg'] !== 'y') ||
    ($prefs['feature_wiki_allowhtml'] === 'y' && $tiki_p_use_HTML === 'y' && $_SESSION['wysiwyg'] !== 'y') ||
    $prefs['feature_wiki_import_html'] === 'y' ||
    $prefs['wiki_comments_allow_per_page'] !== 'n' ||
    $prefs['markdown_enabled'] === 'y' ||
    ($tiki_p_admin_wiki === 'y' && $prefs['feature_wiki_import_page'] === 'y') ||
    ($_SESSION['wysiwyg'] !== 'y' && ($prefs['feature_wiki_attachments'] === 'y' && ($tiki_p_wiki_attach_files === 'y' && $tiki_p_wiki_admin_attachments === 'y'))) ||
    strtolower($page) !== 'sandbox' &&
            ($prefs['wiki_feature_copyrights'] === 'y' ||
            ($prefs['feature_freetags'] === 'y' && $tiki_p_freetags_tag === 'y') ||
            $prefs['feature_wiki_icache'] === 'y' ||
            $prefs['feature_contribution'] === 'y' ||
            $prefs['feature_wiki_structure'] === 'y' ||
            $prefs['wiki_feature_copyrights'] === 'y' ||
            ($tiki_p_admin_wiki === 'y' && $prefs['wiki_authors_style_by_page'] === 'y')) ||    // end not sandbox
        ($prefs['feature_wiki_description'] === 'y' || $prefs['metatag_pagedesc'] === 'y') ||
        $prefs['feature_wiki_footnotes'] === 'y' ||
        ($prefs['feature_wiki_ratings'] === 'y' && $tiki_p_wiki_admin_ratings === 'y') ||
        $prefs['feature_multilingual'] === 'y' ||
        $prefs['namespace_enabled'] === 'y' ||
        $prefs['site_layout_per_object'] === 'y' ||
        ! empty($prefs['geo_locate_wiki']) && $prefs['geo_locate_wiki'] === 'y'
) {
    $smarty->assign('showPropertiesTab', 'y');
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the Edit Template or language check
$smarty->assign('showtags', 'n');
$smarty->assign('qtnum', '1');
$smarty->assign('qtcycle', '');
$smarty->assign('outputType', (isset($info['outputType'])) ? $info['outputType'] : '');

possibly_set_pagedata_to_pretranslation_of_source_page();

if ($need_lang) {
    $smarty->display('tiki-choose_page_language.tpl');
} else {
    // if ajax_autosave is disabled, create an alert to inform the user
    if ($prefs['ajax_autosave'] == 'n') {
        $content = tr('Feature <strong>%0</strong>  disabled.', 'ajax_autosave');

        if (Perms::get()->admin) {
            $smarty = TikiLib::lib('smarty');

            $content .= "<form method='post' action='tiki-admin.php'>";
            $content .= str_replace('"', "&quot;", smarty_function_preference(['name' => 'ajax_autosave'], $smarty->getEmptyInternalTemplate()));
            $content .= str_replace('"', "&quot;", smarty_function_ticket([], $smarty->getEmptyInternalTemplate()));
            $content .= "<input type='submit' class='btn btn-primary btn-sm' value='";
            $content .= smarty_modifier_escape(tra('Apply')) . "'>";
            $content .= '</form>';
        }
        $remrepeat = false;
        $smartyTemplate = $smarty->getEmptyInternalTemplate();
        $remarksbox = str_replace('"', "'", smarty_block_remarksbox(['type' => 'warning', 'title' => 'Autosave', 'close' => 'y'], $content, $smartyTemplate, $remrepeat));

        $smarty->assign('alert_content', $remarksbox);
    }
    $smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
    $smarty->display('tiki-editpage.tpl');
}
/**
 * @param $chkURL
 * @return bool
 */
function isURL($chkURL)
{
    $rc = (false !== parse_url($chkURL));
    return $rc;
}
