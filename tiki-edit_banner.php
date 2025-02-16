<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'bannerId'                    => 'int',           //post
            'removeZone'                  => 'string',        //post
            'save'                        => 'bool',          //post
            'create_zone'                 => 'bool',          //post
            'fromTimeMeridian'            => 'int',           //post
            'fromTimeHour'                => 'digits',        //post
            'toTimeMeridian'              => 'int',           //post
            'toTimeHour'                  => 'digits',        //post
            'fromDate_Month'              => 'string',        //post
            'fromDate_Day'                => 'digits',        //post
            'fromDate_Year'               => 'digits',        //post
            'toDate_Month'                => 'string',        //post
            'toDate_Day'                  => 'digits',        //post
            'toDate_Year'                 => 'digits',        //post
            'fromTimeMinute'              => 'digits',        //post
            'toTimeMinute'                => 'digits',        //post
            'client'                      => 'string',        //post
            'maxImpressions'              => 'int',           //post
            'maxUserImpressions'          => 'int',           //post
            'maxClicks'                   => 'int',           //post
            'HTMLData'                    => 'html',          //post
            'fixedURLData'                => 'url',           //post
            'textData'                    => 'xss',           //post
            'zone'                        => 'string',        //post
            'onlyInURIs'                  => 'string',        //post
            'exceptInURIs'                => 'string',        //post
            'url'                         => 'url',           //post
            'use'                         => 'string',        //post
            'useDates'                    => 'bool',          //post
            'Dmon'                        => 'bool',          //post
            'Dtue'                        => 'bool',          //post
            'Dwed'                        => 'bool',          //post
            'Dthu'                        => 'bool',          //post
            'Dfri'                        => 'bool',          //post
            'Dsat'                        => 'bool',          //post
            'Dsun'                        => 'bool',          //post
            'zoneName'                    => 'string',        //post
            'imageName'                   => 'string',        //post
            'imageType'                   => 'string',        //post
            'imageData'                   => 'string',        //post
        ],
    ],
];
require_once('tiki-setup.php');

$tikilib = TikiLib::lib('tiki');
$bannerlib = TikiLib::lib('banner');

$access->check_feature('feature_banners');
$access->check_permission('tiki_p_admin_banners');
//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

if (isset($_REQUEST["bannerId"]) && $_REQUEST["bannerId"] > 0) {
    $info = $bannerlib->get_banner($_REQUEST["bannerId"]);

    if (! $info) {
        $smarty->assign('msg', tra("Banner not found"));

        $smarty->display("error.tpl");
        die;
    }

    // Check user is admin or the client
    if (($user != $info["client"]) && ($tiki_p_admin_banners != 'y')) {
        $smarty->assign('errortype', 401);
        $smarty->assign('msg', tra("You do not have permission to edit this banner"));

        $smarty->display("error.tpl");
        die;
    }

    $fromTime = substr($info["hourFrom"], 0, 2) . ":" . substr($info["hourFrom"], 2, 2);
    $toTime = substr($info["hourTo"], 0, 2) . ":" . substr($info["hourTo"], 2, 2);
    $smarty->assign('bannerId', $info["bannerId"]);
    $smarty->assign('client', $info["client"]);
    $smarty->assign('maxImpressions', $info["maxImpressions"]);
    $smarty->assign('maxUserImpressions', $info["maxUserImpressions"]);
    $smarty->assign('maxClicks', $info["maxClicks"]);
    $smarty->assign('fromDate', $info["fromDate"]);
    $smarty->assign('toDate', $info["toDate"]);
    $smarty->assign('useDates', $info["useDates"]);
    $smarty->assign("fromTime", $fromTime);
    $smarty->assign("toTime", $toTime);
    $smarty->assign("Dmon", $info["mon"]);
    $smarty->assign("Dtue", $info["tue"]);
    $smarty->assign("Dwed", $info["wed"]);
    $smarty->assign("Dthu", $info["thu"]);
    $smarty->assign("Dfri", $info["fri"]);
    $smarty->assign("Dsat", $info["sat"]);
    $smarty->assign("Dsun", $info["sun"]);
    $smarty->assign("use", $info["which"]);
    $smarty->assign('onlyInURIs', $info['onlyInURIs']);
    $smarty->assign('exceptInURIs', $info['exceptInURIs']);
    $smarty->assign("zone", $info["zone"]);
    $smarty->assign("HTMLData", $info["HTMLData"]);
    $smarty->assign("fixedURLData", $info["fixedURLData"]);
    $smarty->assign("textData", $info["textData"]);
    $smarty->assign("url", $info["url"]);
    $smarty->assign("imageName", $info["imageName"]);
    $smarty->assign("imageData", urlencode($info["imageData"]));
    $smarty->assign("imageType", $info["imageType"]);
    $smarty->assign("hasImage", 'n');

    if (strlen($info["imageData"]) > 0) {
        $tmpfname = $prefs['tmpDir'] . "/bannerimage" . "." . $_REQUEST["bannerId"];
        $data = $info["imageData"];
        $fp = fopen($tmpfname, "wb");
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
            $smarty->assign('tempimg', $tmpfname);
            $smarty->assign('hasImage', 'y');
        } else {
            $smarty->assign('tempimg', 'n');
            $smarty->assign('hasImage', 'n');
        }
    }
} else {
    $smarty->assign('client', '');
    $smarty->assign('maxImpressions', -1);
    $smarty->assign('maxUserImpressions', -1);
    $smarty->assign('maxClicks', -1);
    $smarty->assign('fromDate', $tikilib->now);
    $cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $tikilib->now));
    $smarty->assign('toDate', $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1));
    $smarty->assign('useDates', 'n');
    $smarty->assign('fromTime', '00:00');
    $smarty->assign('toTime', '23:59');
    // Variables for dates are fromDate_ and toDate_ plus fromTime_ and toTime_
    $smarty->assign('Dmon', 'y');
    $smarty->assign('Dtue', 'y');
    $smarty->assign('Dwed', 'y');
    $smarty->assign('Dthu', 'y');
    $smarty->assign('Dfri', 'y');
    $smarty->assign('Dsat', 'y');
    $smarty->assign('Dsun', 'y');
    $smarty->assign('bannerId', 0);
    $smarty->assign('zone', '');
    $smarty->assign('onlyInURIS', '');
    $smarty->assign('exceptInURIS', '');
    $smarty->assign('use', 'useHTML');
    $smarty->assign('HTMLData', '');
    $smarty->assign('fixedURLData', '');
    $smarty->assign('textData', '');
    $smarty->assign('url', '');
    $smarty->assign('imageData', '');
    $smarty->assign('hasImage', 'n');
    $smarty->assign('imageName', '');
    $smarty->assign('imageType', '');
}

if (isset($_REQUEST["removeZone"]) && $access->checkCsrf()) {
    $bannerlib->banner_remove_zone($_REQUEST["removeZone"]);
}

// Now assign if the set button was pressed
if (isset($_REQUEST["save"]) || isset($_REQUEST["create_zone"])) {
    $access->checkCsrf();
    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_REQUEST['fromTimeMeridian'])) {
        $_REQUEST['fromTimeHour'] = date('H', strtotime($_REQUEST['fromTimeHour'] . ':00 ' . $_REQUEST['fromTimeMeridian']));
    }
    if (! empty($_REQUEST['toTimeMeridian'])) {
        $_REQUEST['toTimeHour'] = date('H', strtotime($_REQUEST['toTimeHour'] . ':00 ' . $_REQUEST['toTimeMeridian']));
    }
    $fromDate = mktime(0, 0, 0, $_REQUEST["fromDate_Month"], $_REQUEST["fromDate_Day"], $_REQUEST["fromDate_Year"]);
    $toDate = mktime(0, 0, 0, $_REQUEST["toDate_Month"], $_REQUEST["toDate_Day"], $_REQUEST["toDate_Year"]);
    $fromTime = '' . $_REQUEST["fromTimeHour"] . $_REQUEST["fromTimeMinute"] . '';
    $toTime = '' . $_REQUEST["toTimeHour"] . $_REQUEST["toTimeMinute"] . '';
    $smarty->assign('fromDate', $fromDate);
    $smarty->assign('toDate', $toDate);
    $smarty->assign('fromTime', $_REQUEST["fromTimeHour"] . ':' . $_REQUEST["fromTimeMinute"]);
    $smarty->assign('toTime', $_REQUEST["toTimeHour"] . ':' . $_REQUEST["toTimeMinute"]);
    $smarty->assign('client', $_REQUEST["client"]);
    $smarty->assign('maxImpressions', $_REQUEST["maxImpressions"]);
    $smarty->assign('maxUserImpressions', $_REQUEST["maxUserImpressions"]);
    $smarty->assign('maxClicks', $_REQUEST["maxClicks"]);
    $smarty->assign('HTMLData', $_REQUEST["HTMLData"] ?? '');
    $smarty->assign('fixedURLData', $_REQUEST["fixedURLData"] ?? '');
    $smarty->assign('textData', $_REQUEST["textData"] ?? '');

    if (isset($_REQUEST["zone"])) {
        $smarty->assign('zone', $_REQUEST["zone"]);
    } else {
        $smarty->assign('zone', '');
    }
    $smarty->assign('onlyInURIs', $_REQUEST['onlyInURIs']);
    $smarty->assign('exceptInURIs', $_REQUEST['exceptInURIs']);
    $smarty->assign('url', $_REQUEST["url"]);

    if (isset($_REQUEST["use"])) {
        $smarty->assign('use', $_REQUEST["use"]);
    }

    if (isset($_REQUEST["useDates"]) && $_REQUEST["useDates"] == 'on') {
        $smarty->assign('useDates', 'y');

        $useDates = 'y';
    } else {
        $smarty->assign('useDates', 'n');

        $useDates = 'n';
    }

    if (isset($_REQUEST["Dmon"]) && $_REQUEST["Dmon"] == 'on') {
        $smarty->assign('Dmon', 'y');

        $Dmon = 'y';
    } else {
        $smarty->assign('Dmon', 'n');

        $Dmon = 'n';
    }

    if (isset($_REQUEST["Dtue"]) && $_REQUEST["Dtue"] == 'on') {
        $smarty->assign('Dtue', 'y');

        $Dtue = 'y';
    } else {
        $smarty->assign('Dtue', 'n');

        $Dtue = 'n';
    }

    if (isset($_REQUEST["Dwed"]) && $_REQUEST["Dwed"] == 'on') {
        $smarty->assign('Dwed', 'y');

        $Dwed = 'y';
    } else {
        $smarty->assign('Dwed', 'n');

        $Dwed = 'n';
    }

    if (isset($_REQUEST["Dthu"]) && $_REQUEST["Dthu"] == 'on') {
        $smarty->assign('Dthu', 'y');

        $Dthu = 'y';
    } else {
        $smarty->assign('Dthu', 'n');

        $Dthu = 'n';
    }

    if (isset($_REQUEST["Dfri"]) && $_REQUEST["Dfri"] == 'on') {
        $smarty->assign('Dfri', 'y');

        $Dfri = 'y';
    } else {
        $smarty->assign('Dfri', 'n');

        $Dfri = 'n';
    }

    if (isset($_REQUEST["Dsat"]) && $_REQUEST["Dsat"] == 'on') {
        $smarty->assign('Dsat', 'y');

        $Dsat = 'y';
    } else {
        $smarty->assign('Dsat', 'n');

        $Dsat = 'n';
    }

    if (isset($_REQUEST["Dsun"]) && $_REQUEST["Dsun"] == 'on') {
        $smarty->assign('Dsun', 'y');

        $Dsun = 'y';
    } else {
        $smarty->assign('Dsun', 'n');

        $Dsun = 'n';
    }

    $smarty->assign('bannerId', $_REQUEST["bannerId"]);

    if (isset($_REQUEST["create_zone"])) {
        $bannerlib->banner_add_zone($_REQUEST["zoneName"]);
    }

    // If we have an upload then process the upload and setup the data in a field
    // that will be hidden is this is a nightmare?
    $imgname = $_REQUEST["imageName"];
    $imgtype = $_REQUEST["imageType"];

    if (isset($_FILES['userfile1']) && $_FILES['userfile1']['error'] === UPLOAD_ERR_OK) {
        if (is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
            $filegallib = TikiLib::lib('filegal');
            try {
                $filegallib->assertUploadedFileIsSafe($_FILES['userfile1']['tmp_name'], $_FILES['userfile1']['name']);
            } catch (Exception $e) {
                $smarty->assign('errortype', 403);
                $smarty->assign('msg', $e->getMessage());
                $smarty->display("error.tpl");
                die;
            }
            $fp = fopen($_FILES['userfile1']['tmp_name'], "rb");

            $data = fread($fp, filesize($_FILES['userfile1']['tmp_name']));
            fclose($fp);
            $imgtype = $_FILES['userfile1']['type'];
            $imgsize = $_FILES['userfile1']['size'];
            $imgname = $_FILES['userfile1']['name'];
            $smarty->assign('imageData', urlencode($data));
            $smarty->assign('imageName', $imgname);
            $smarty->assign('imageType', $imgtype);
            $_REQUEST["imageData"] = urlencode($data);
            $_REQUEST["imageName"] = $imgname;
            $_REQUEST["imageType"] = $imgtype;
        } else {
            switch ($_FILES['userfile1']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    Feedback::error(tra("The uploaded file exceeds the upload_max_filesize directive in php.ini."));
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    Feedback::error(tra("The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form."));
                    break;
                case UPLOAD_ERR_PARTIAL:
                    Feedback::error(tra("The uploaded file was only partially uploaded."));
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    Feedback::error(tra("Missing a temporary folder."));
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    Feedback::error(tra("Failed to write file to disk."));
                    break;
                case UPLOAD_ERR_EXTENSION:
                    Feedback::error(tra("A PHP extension stopped the file upload."));
                    break;
                case UPLOAD_ERR_NO_FILE:
                    Feedback::error(tra("No file was uploaded."));
                    break;
                default:
                    Feedback::error(tra("Unknown error."));
                    break;
            }
        }
    }

    $smarty->assign('imageData', $_REQUEST["imageData"]);
    $smarty->assign('tempimg', 'n');

    if (strlen($_REQUEST["imageData"]) > 0) {
        $tmpfname = tempnam($prefs['tmpDir'], "TMPIMG") . $imgname;

        $fp = fopen($tmpfname, "w");

        if ($fp) {
            fwrite($fp, urldecode($_REQUEST["imageData"]));

            fclose($fp);
            $smarty->assign('tempimg', $tmpfname);
            $smarty->assign('hasImage', 'y');
        } else {
            $smarty->assign('hasImage', 'n');
        }
    }

    if (! isset($_REQUEST["create_zone"])) {
        $bannerId = $bannerlib->replace_banner(
            $_REQUEST["bannerId"],
            $_REQUEST["client"],
            $_REQUEST["url"],
            '',
            '',
            $_REQUEST["use"],
            $_REQUEST["imageData"],
            $_REQUEST["imageType"],
            $_REQUEST["imageName"],
            $_REQUEST["HTMLData"],
            $_REQUEST["fixedURLData"],
            $_REQUEST["textData"],
            $fromDate,
            $toDate,
            $useDates,
            $Dmon,
            $Dtue,
            $Dwed,
            $Dthu,
            $Dfri,
            $Dsat,
            $Dsun,
            $fromTime,
            $toTime,
            $_REQUEST["maxImpressions"],
            $_REQUEST["maxClicks"],
            $_REQUEST["zone"] ?? null,
            $_REQUEST["maxUserImpressions"],
            $_REQUEST['onlyInURIs'],
            $_REQUEST['exceptInURIs']
        );

        header("location:tiki-list_banners.php");
    }
}

$zones = $bannerlib->banner_get_zones();
$smarty->assign_by_ref('zones', $zones);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_banner.tpl');
$smarty->display("tiki.tpl");
