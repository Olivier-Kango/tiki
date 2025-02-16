<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'wiki page';
require_once('tiki-setup.php');

$structlib = TikiLib::lib('struct');
$wikilib = TikiLib::lib('wiki');

if ($prefs['feature_wiki'] != 'y') {
    $smarty->assign('msg', tra("This feature is disabled") . ": feature_wiki");

    $smarty->display("error_raw.tpl");
    die;
}

// Create the HomePage if it doesn't exist
if (! $tikilib->page_exists($prefs['wikiHomePage'])) {
    $tikilib->create_page($prefs['wikiHomePage'], 0, '', date("U"), 'Tiki initialization');
}

if (! isset($_SESSION["thedate"])) {
    $thedate = date("U");
} else {
    $thedate = $_SESSION["thedate"];
}

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["page"])) {
    $_REQUEST["page"] = $wikilib->get_default_wiki_page();
}
$page = $_REQUEST['page'];
$smarty->assign('page', $page);

// If the page doesn't exist
if (! ($info = $tikilib->get_page_info($page))) {
    // First, try cleaning the url to see if it matches an existing page.
    $wikilib->clean_url_suffix_and_redirect($page, $type = '', $path = '', $prefix = '');

    // If after cleaning the url, the page does not exist then display an error
    $smarty->assign('msg', tra("Page cannot be found"));
    $smarty->display("error_raw.tpl");
    die;
}

require_once 'lib/wiki/renderlib.php';
$pageRenderer = new WikiRenderer($info, $user);
$objectperms = $pageRenderer->applyPermissions();

if ($prefs['flaggedrev_approval'] == 'y' && isset($_REQUEST['latest']) && $objectperms->wiki_view_latest) {
    $pageRenderer->forceLatest();
}

$access->check_permission('tiki_p_view', '', 'wiki page', $page);

// BreadCrumbNavigation here
// Remember to reverse the array when posting the array

if (! isset($_SESSION["breadCrumb"])) {
    $_SESSION["breadCrumb"] = [];
}

if (! in_array($page, $_SESSION["breadCrumb"])) {
    if (count($_SESSION["breadCrumb"]) > $prefs['userbreadCrumb']) {
        array_shift($_SESSION["breadCrumb"]);
    }

    array_push($_SESSION["breadCrumb"], $page);
} else {
    // If the page is in the array move to the last position
    $pos = array_search($page, $_SESSION["breadCrumb"]);

    unset($_SESSION["breadCrumb"][$pos]);
    array_push($_SESSION["breadCrumb"], $page);
}

// Now increment page hits since we are visiting this page
$tikilib->add_hit($page);

// Verify lock status
if ($info["flag"] == 'L') {
    $smarty->assign('lock', true);
} else {
    $smarty->assign('lock', false);
}

// If not locked and last version is user version then can undo
$smarty->assign('canundo', 'n');

if ($info["flag"] != 'L' && (($tiki_p_edit == 'y' && $info["user"] == $user) || ($tiki_p_remove == 'y'))) {
    $smarty->assign('canundo', 'y');
}

$smarty->assign('page_id', $info['page_id']);

if ($tiki_p_admin_wiki == 'y') {
    $smarty->assign('canundo', 'y');
}

if (isset($_REQUEST['pagenum']) && $_REQUEST['pagenum'] > 0) {
    $pageRenderer->setPageNumber((int) $_REQUEST['pagenum']);
}

$pageRenderer->useRaw();

include_once('tiki-section_options.php');
$pageRenderer->runSetups();

// Process page display options
$wikilib->processPageDisplayOptions(TikiLib::lib('header'));

// Display the Index Template

// If the url has the param "download", ask the browser to download it (instead of displaying it)
if (isset($_REQUEST['download']) && $_REQUEST['download'] !== 'n') {
    if (isset($_REQUEST["filename"])) {
        $filename = $_REQUEST['filename'];
    } else {
        $filename = $page;
    }
    $filename = str_replace(['?',"'",'"',':','/','\\'], '_', $filename);    // clean some bad chars
    // add &css to the URL to transfer it as text/css mime type
    if (isset($_REQUEST['css']) && $_REQUEST['css'] !== 'n') {
        header("Content-type: text/css; charset=utf-8");
    } else {
        header("Content-type: text/plain; charset=utf-8");
    }
    header("Content-Disposition: attachment; filename=\"$filename\"");
}

if (isset($_REQUEST['xml'])) {
    header("Content-type: application/xml; charset=utf-8");
}

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// add &full to URL to output the whole html head and body
if (isset($_REQUEST['full']) && $_REQUEST['full'] != 'n') {
    $smarty->assign('mid', 'tiki-show_page_raw.tpl');
    // use tiki_full to include include CSS and JavaScript
    $smarty->display("tiki_full.tpl");
} elseif (isset($_REQUEST['textonly']) && $_REQUEST['textonly'] != 'n') {
    $output = $smarty->fetch("tiki-show_page_raw.tpl");
    $output = strip_tags($output);
    $output = $tikilib->htmldecode($output);
    echo $output;
} elseif (isset($_REQUEST['gtype']) && $_REQUEST['gtype'] == 'svg') { # this case is needed for mod PluginR to successfully produce svg versions of the png charts generated.
    $output = $smarty->fetch("tiki-show_page_raw.tpl");
    $output = $tikilib->htmldecode($output);
    preg_match('#(<\?xml.*</svg>)#sm', $output, $output);
    echo $output[0];
    echo "\n";
} elseif (isset($_REQUEST['gtype']) && $_REQUEST['gtype'] == 'pdf') { # this case is needed for mod PluginR to successfully produce pdf versions of the png charts generated.
    $output = $smarty->fetch("tiki-show_page_raw.tpl");
    $output = $tikilib->htmldecode($output);
    preg_replace('#^%%EOF$(.*)#sm', '%%EOF', $output);
    echo "\n";
} else {
    // otherwise just the contents of the page without body etc
    $smarty->display("tiki-show_page_raw.tpl");
}
