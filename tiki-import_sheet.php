<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'sheet';
require_once('tiki-setup.php');
$sheetlib = TikiLib::lib("sheet");

$access->check_feature('feature_sheet');

$info = $sheetlib->get_sheet_info($_REQUEST['sheetId']);
if (empty($info)) {
    $smarty->assign('Incorrect parameter');
    $smarty->display('error.tpl');
    die;
}

$objectperms = Perms::get('sheet', $_REQUEST['sheetId']);
if ($tiki_p_admin != 'y' && ! $objectperms->view_sheet && ! ($user && $info['author'] == $user)) {
    $smarty->assign('msg', tra('Permission denied'));
    $smarty->display('error.tpl');
    die;
}
$smarty->assign('sheetId', $_REQUEST["sheetId"]);

$smarty->assign('title', $info['title']);
$smarty->assign('description', $info['description']);

$smarty->assign('page_mode', 'form');

// Process the insertion or modification of a gallery here

$grid = new TikiSheet();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $access->checkCsrf()) {
    $smarty->assign('page_mode', 'submit');

    $sheetId = $_REQUEST['sheetId'];
    $handler = $_REQUEST['handler'];
    $encoding = $_REQUEST['encoding'];

    // Instantiate the handler
    switch ($handler) {
        case 'TikiSheetWikiTableHandler': // Well known, special handlers
            $handler = new $handler($_POST['page']);
            break;
        default: // All file based handlers registered
            $handler_ = new TikiSheet();
            if (! in_array($handler, $handler_->getHandlerList())) {
                $smarty->assign('msg', "Handler is not allowed.");
                $smarty->display("error.tpl");
                die;
            }

            if (file_exists($_FILES['file']['tmp_name'])) {
                $handler = new $handler($_FILES['file']['tmp_name'], $encoding, 'UTF-8');
            } else {
                $smarty->assign('msg', "File path does not exist");
                $smarty->display("error.tpl");
                die;
            }
    }

    if (! $grid->import($handler)) {
        $smarty->assign('msg', "Impossible to import the file.");
        $smarty->display("error.tpl");
        die;
    }

    $handler = new TikiSheetDatabaseHandler($sheetId);
    $grid->export($handler);

    ob_start();
    $handler = new TikiSheetOutputHandler();
    $grid->export($handler);
    $smarty->assign("grid_content", ob_get_contents());
    ob_end_clean();
} else {
    $list = [];
    $encoding = new Encoding();
    $charsetList = $encoding->get_input_supported_encodings();

    $handlers_ = new TikiSheet();
    $handlers = $handlers_->getHandlerList();

    foreach ($handlers as $key => $handler) {
        $temp = new $handler();
        if (! $temp->supports(TIKISHEET_LOAD_DATA | TIKISHEET_LOAD_CALC)) {
            continue;
        }

        $list[$key] = [
            "name" => $temp->name(),
            "version" => $temp->version(),
            "class" => $handler
        ];
    }

    $smarty->assign_by_ref("handlers", $list);
    $smarty->assign_by_ref("charsets", $charsetList);
}

$cat_type = 'sheet';
$cat_objid = $_REQUEST["sheetId"];
include_once("categorize_list.php");

include_once('tiki-section_options.php');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-import-sheets.tpl');
$smarty->display("tiki.tpl");
