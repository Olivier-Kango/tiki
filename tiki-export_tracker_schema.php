<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');
$access->check_feature('feature_trackers');

$trklib = TikiLib::lib('trk');
$definitions = Tracker_Definition::getAll();

if (empty($definitions)) {
    $smarty->assign('msg', tra('No tracker found'));
    $smarty->display('error.tpl');
    die;
}

$requestedTrackerIds = $_REQUEST["trackerIds"] ?? [];
$smarty->assign('requestedTrackerIds', $requestedTrackerIds);
if (! is_array($requestedTrackerIds)) {
    $smarty->assign('msg', tra('The trackerIds parameter must be an array of (possibly just one) tracker ids'));
    $smarty->display('error.tpl');
    die;
}

$skipAttributes = ! empty($_REQUEST["skipAttributes"]) ?? false;
$skipRelations = ! empty($_REQUEST["skipRelations"]) ?? false;
$includePermNames = empty($_REQUEST["includePermNames"]) ? false : true;

$availableTrackers = [];
$goback = count($requestedTrackerIds) > 1 ? 'trackers' : 'tracker';
$smarty->assign('goback', $goback);
foreach ($definitions as $tracker) {
    $tikilib->get_perm_object($tracker->getID(), 'tracker', $tracker->getInformation());
    $access->check_permission('tiki_p_export_tracker', tra('Export Tracker'), 'tracker', $tracker->getID());
    $availableTrackers[$tracker->getID()] = $tracker;
}

if (! $requestedTrackerIds) {
    //If no specific trackers were requested, add them all
    $trackerIds = array_keys($availableTrackers);
} else {
    $trackerIds = $requestedTrackerIds;
}

$headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . "/svg-pan-zoom/dist/svg-pan-zoom.min.js");
$headerlib->add_jsfile('lib/jquery_tiki/tiki-export_tracker_schema.js');

$idTracker = isset($_REQUEST["trackerIds"]) ? $_REQUEST["trackerIds"][0] : '';
$smarty->assign('idTracker', $idTracker);
$smarty = TikiLib::lib('smarty');
require_once("export-tracker_schema.php");
$mermaidText = exportMermaidER($title, $entities, $relationships, $skipAttributes, $includePermNames);
$mermaidOutput = renderMermaid($mermaidText);
if (isset($_REQUEST['export'])) {
    $export = $_REQUEST['export'];
} else {
    $export = 'svgFormat';
}

$mermaidText = exportMermaidER($title, $entities, $relationships, $skipAttributes, $includePermNames, true);
$smarty->assign('export', $export);
$smarty->assign('textPlain', $mermaidText);
$smarty->assign('contentmain', $mermaidOutput);
$smarty->assign('mid', 'tiki-export_tracker_schema.tpl');
$smarty->display('tiki.tpl');
