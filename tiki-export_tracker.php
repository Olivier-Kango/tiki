<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// still used for export from trackerfilter (only - Tiki 9)

require_once('tiki-setup.php');
$access->check_feature('feature_trackers');
if (! isset($_REQUEST['trackerId'])) {
    $smarty->assign('msg', tra('No tracker indicated'));
    $smarty->display('error.tpl');
    die;
}
$trklib = TikiLib::lib('trk');
@ini_set('max_execution_time', 0);

$tracker_info = $trklib->get_tracker($_REQUEST['trackerId']);
if (empty($tracker_info)) {
    $smarty->assign('msg', tra('No tracker indicated'));
    $smarty->display('error.tpl');
    die;
}
if ($t = $trklib->get_tracker_options($_REQUEST['trackerId'])) {
    $tracker_info = array_merge($tracker_info, $t);
}
$tikilib->get_perm_object($_REQUEST['trackerId'], 'tracker', $tracker_info);
$access->check_permission('tiki_p_export_tracker', tra('Export Tracker'), 'tracker', $_REQUEST['trackerId']);

$smarty->assign_by_ref('trackerId', $_REQUEST['trackerId']);
$smarty->assign_by_ref('tracker_info', $tracker_info);

$filters = [];
if (! empty($_REQUEST['listfields'])) {
    if (is_string($_REQUEST['listfields'])) {
        $filters['fieldId'] = preg_split('/[,:]/', $_REQUEST['listfields']);
    } elseif (is_array($_REQUEST['listfields'])) {
        $filters['fieldId'] = $_REQUEST['listfields'];
    }
} elseif (isset($_REQUEST['which']) && $_REQUEST['which'] == 'ls') {
    $filters['or'] = ['isSearchable' => 'y', 'isTblVisible' => 'y'];
} elseif (isset($_REQUEST['which']) && $_REQUEST['which'] == 'list') {
    $filters['isTblVisible'] = 'y';
}
if ($tiki_p_admin_trackers != 'y') {
    $filters['isHidden'] = ['n', 'c'];
}
if ($tiki_p_tracker_view_ratings != 'y') {
    $filters['not'] = ['type' => 's'];
}
$filters['not'] = ['type' => 'h'];

$fields = $trklib->list_tracker_fields($_REQUEST['trackerId'], 0, -1, 'position_asc', '', true, $filters);
$listfields = [];
foreach ($fields['data'] as $field) {
    $listfields[$field['fieldId']] = $field;
}
// If we have a list of displayed fields, we show only those, so the export matches the online display
if (isset($_REQUEST["displayedFields"])) {
    $displayedFields = explode(':', $_REQUEST["displayedFields"]);
    $listfields = array_intersect_key($listfields, array_flip($displayedFields));
}

if (! isset($_REQUEST['which'])) {
    $_REQUEST['which'] = 'all';
}
if (! isset($_REQUEST['status'])) {
    $_REQUEST['status'] = '';
}
if (! isset($_REQUEST['initial'])) {
    $_REQUEST['initial'] = '';
}
$filterFields = [];
$values = [];
$exactValues = [];
foreach ($_REQUEST as $key => $val) {
    if (substr($key, 0, 2) == 'f_' && ! empty($val) && (! is_array($val) || ! empty($val[0]))) {
        $fieldId = substr($key, 2);
        $filterFields[] = $fieldId;
        if (isset($_REQUEST["x_$fieldId"]) && $_REQUEST["x_$fieldId"] == 't') {
            $exactValues[] = '';
            if (is_array($val)) {
                $values[] = $val;
            } else {
                $values[] = urldecode($val);
            }
        } else {
            if (is_array($val)) {
                $exactValues[] = $val;
            } else {
                $exactValues[] = urldecode($val);
            }
            $values[] = '';
        }
    }
}
$smarty->assign_by_ref('listfields', $listfields);

if (isset($_REQUEST['showStatus'])) {
    $showStatus = $_REQUEST['showStatus'] == 'on' ? 'y' : 'n';
} else {
    $showStatus = 'n';
}
$smarty->assign_by_ref('showStatus', $showStatus);

if (isset($_REQUEST['showItemId'])) {
    $showItemId = $_REQUEST['showItemId'] == 'on' ? 'y' : 'n';
} else {
    $showItemId = 'n';
}
$smarty->assign_by_ref('showItemId', $showItemId);

if (isset($_REQUEST['showCreated'])) {
    $showCreated = $_REQUEST['showCreated'] == 'on' ? 'y' : 'n';
} else {
    $showCreated = 'n';
}
$smarty->assign_by_ref('showCreated', $showCreated);

if (isset($_REQUEST['showLastModif'])) {
    $showLastModif = $_REQUEST['showLastModif'] == 'on' ? 'y' : 'n';
} else {
    $showLastModif = 'n';
}
$smarty->assign_by_ref('showLastModif', $showLastModif);

if (isset($_REQUEST['showLastModifBy'])) {
    $showLastModifBy = $_REQUEST['showLastModifBy'] == 'on' ? 'y' : 'n';
} else {
    $showLastModifBy = 'n';
}
$smarty->assign_by_ref('showLastModifBy', $showLastModifBy);

if (isset($_REQUEST['parse'])) {
    $parse = $_REQUEST['parse'] == 'on' ? 'y' : 'n';
} else {
    $parse = 'n';
}
$smarty->assign_by_ref('parse', $parse);

if (empty($_REQUEST['encoding'])) {
    $_REQUEST['encoding'] = 'ISO-8859-1';
}
if (empty($_REQUEST['separator'])) {
    $_REQUEST['separator'] = ',';
}
$smarty->assign_by_ref('separator', $_REQUEST['separator']);
if (empty($_REQUEST['delimitorL'])) {
    $_REQUEST['delimitorL'] = '"';
}
$smarty->assign_by_ref('delimitorL', $_REQUEST['delimitorL']);
if (empty($_REQUEST['delimitorR'])) {
    $_REQUEST['delimitorR'] = '"';
}
$smarty->assign_by_ref('delimitorR', $_REQUEST['delimitorR']);
if (empty($_REQUEST['CR'])) {
    $_REQUEST['CR'] = '%%%';
}
$smarty->assign_by_ref('CR', $_REQUEST['CR']);

$fp = null;

if (! empty($_REQUEST['debug'])) {
    $fp = fopen($prefs['tmpDir'] . '/' . tra('tracker') . "_" . $_REQUEST['trackerId'] . ".csv", 'w');
    echo 'ouput:' . $prefs['tmpDir'] . '/' . tra('tracker') . "_" . $_REQUEST['trackerId'] . ".csv";
} else {
    // Compression of the stream may corrupt files on windows
    if ($prefs['feature_obzip'] != 'y') {
        ob_end_clean();
    }
    ini_set('zlib.output_compression', 'Off');

    $extension = empty($_REQUEST['zip']) ? '.csv' : '.zip';
    if (! empty($_REQUEST['file'])) {
        if (preg_match('/' . $extension . '$/', $_REQUEST['file'])) {
            $file = $_REQUEST['file'];
        } else {
            $file = $_REQUEST['file'] . $extension;
        }
    } else {
        $file = tra('tracker') . '_' . $_REQUEST['trackerId'] . $extension;
    }
    if (! empty($_REQUEST['zip'])) {
        $tmpCsv = tempnam($prefs['tmpDir'], 'tracker_' . $_REQUEST['trackerId']) . '.csv';
        /*debug*/$tmpCsv = $prefs['tmpDir'] . '/' . 'tracker_' . $_REQUEST['trackerId'] . '.csv';
        if (! ($fp = fopen($tmpCsv, 'w'))) {
            $smarty->assign('msg', tra('The file cannot be opened') . ' ' . $tmpCsv);
            $smarty->display('error.tpl');
            die;
        }
        if (! ($archive = new ZipArchive())) {
            $smarty->assign('msg', tra('Problem zip initialisation'));
            $smarty->display('error.tpl');
            die;
        }
        $tmpZip = $prefs['tmpDir'] . '/' . $file;
        if (! ($archive->open($tmpZip, ZIPARCHIVE::OVERWRITE))) {
            $smarty->assign('msg', tra('The file cannot be opened') . ' ' . $prefs['tmpDir'] . '/' . $file);
            $smarty->display('error.tpl');
            die;
        }

        header('Content-Type: application/zip');
        header('Content-Transfer-Encoding: binary');
    } else {
        header("Content-type: text/comma-separated-values; charset:" . $_REQUEST['encoding']);
    }
    header("Content-Disposition: attachment; filename=$file");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
}

$offset = 0;
$maxRecords = 100;
// TODO refactor using \TrackerLib::get_default_sort_order
if ($tracker_info['defaultOrderKey'] == -1) {
    $sort_mode = 'lastModif';
} elseif ($tracker_info['defaultOrderKey'] == -2) {
    $sort_mode = 'created';
} elseif ($tracker_info['defaultOrderKey'] == -3) {
    $sort_mode = 'itemId';
} elseif (isset($tracker_info['defaultOrderKey'])) {
    $sort_mode = 'f_' . $tracker_info['defaultOrderKey'];
} else {
    $sort_mode = 'itemId';
}
if (isset($tracker_info['defaultOrderDir'])) {
    $sort_mode .= "_" . $tracker_info['defaultOrderDir'];
} else {
        $sort_mode .= "_asc";
}
$heading = 'y';
$smarty->assign_by_ref('heading', $heading);
if (empty($_REQUEST['itemId'])) {
    while (($items = $trklib->list_items($_REQUEST['trackerId'], $offset, $maxRecords, $sort_mode, $listfields, $filterFields, $values, $_REQUEST['status'], $_REQUEST['initial'], $exactValues)) && ! empty($items['data'])) {
        // still need to filter the fields that are view only by the admin and the item creator
        if ($tracker_info['useRatings'] == 'y') {
            foreach ($items['data'] as $f => $v) {
                $items['data'][$f]['my_rate'] = $tikilib->get_user_vote("tracker." . $_REQUEST['trackerId'] . '.' . $items['data'][$f]['itemId'], $user);
            }
        }
        $smarty->assign_by_ref('items', $items["data"]);

        $data = $smarty->fetch('tiki-export_tracker_item.tpl');
        $data = preg_replace("/^\n/", "", $data);
        if (empty($_REQUEST['encoding']) || $_REQUEST['encoding'] == 'ISO-8859-1') {
            $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
        }

        $offset += $maxRecords;
        $heading = 'n';
        if (! empty($fp)) {
            fwrite($fp, $data);
        } else {
            echo $data;
        }
        if ($tracker_info['useAttachments'] == 'y' && ! empty($_REQUEST['zip'])) {
            foreach ($items['data'] as $v) {
                if (! $trklib->export_attachment($v['itemId'], $archive)) {
                    $smarty->assign('msg', tra('Problem zip'));
                    $smarty->display('error.tpl');
                    die;
                }
            }
        }
    }
} else {
    $items = [];
    $items[] = $trklib->get_tracker_item($_REQUEST['itemId']);
    $items[0]['field_values'] = $trklib->get_item_fields($_REQUEST['trackerId'], $_REQUEST['itemId'], $listfields);
    $smarty->assign_by_ref('items', $items);

    $data = $smarty->fetch('tiki-export_tracker_item.tpl');
    $data = preg_replace("/^\n/", "", $data);
    if (empty($_REQUEST['encoding']) || $_REQUEST['encoding'] == 'ISO-8859-1') {
        $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
    }
    if (! empty($fp)) {
        fwrite($fp, $data);
    } else {
        echo $data;
    }
}
if (! empty($fp)) {
    fclose($fp);
}
if (! empty($_REQUEST['zip'])) {
    $archive->addFile($tmpCsv, str_replace('.zip', '.csv', $file));
    $archive->close();
    readfile($tmpZip);
    unlink($tmpZip);
    unlink($tmpCsv);
}
die;
