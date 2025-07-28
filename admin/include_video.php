<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

$kplayerlist = "";
$kcwText = "";

if ($prefs['feature_kaltura'] === 'y') {
    $kalturaadminlib = TikiLib::lib('kalturaadmin');

    if ($kalturaadminlib->getSessionKey()) {
        // make sure service url ends in a slash
        if (substr($prefs['kaltura_kServiceUrl'], -1) != '/') {
            $prefs['kaltura_kServiceUrl'] = $prefs['kaltura_kServiceUrl'] . '/';
            TikiLib::lib('tiki')->set_preference('kaltura_kServiceUrl', $prefs['kaltura_kServiceUrl']);
        }

        if (empty($prefs['kaltura_kdpUIConf'])) {   // player pref empty
            $playerList = $kalturaadminlib->getPlayersUiConfs();
            if ($playerList) {
                $tikilib->set_preference('kaltura_kdpUIConf', $playerList[0]['id']);
            }
        }
        if (empty($prefs['kaltura_kdpEditUIConf'])) {    // edit mode player pref empty
            $tikilib->set_preference('kaltura_kdpEditUIConf', $prefs['kaltura_kdpUIConf']);
        }
    } else {
        $kcwText = "<div class='adminoptionbox error'>" . tr("Unable to retrieve configuration from Kaltura. Please reload page after setting up the Kaltura Partner Settings section") . "</div>";
        $kplayerlist = "<div class='adminoptionbox error'>" . tr("Unable to retrieve list of valid player IDs. Please reload page after setting up the Kaltura Partner Settings section") . "</div>";
    }
} else {
    $kcwText = "<div class='adminoptionbox error'>" . tr("Kaltura feature disabled") . "</div>";
    $kplayerlist = "<div class='adminoptionbox error'>" . tr("Kaltura feature disabled") . "</div>";
}

// PeerTube feature handling
if ($prefs['feature_peertube'] === 'y') {
    $peertubelib = TikiLib::lib('peertubeuser');

    if (substr($prefs['peertube_service_url'], -1) != '/') {
        $prefs['peertube_service_url'] = $prefs['peertube_service_url'] . '/';
        TikiLib::lib('tiki')->set_preference('peertube_service_url', $prefs['peertube_service_url']);
    }

    // Test PeerTube API connection
    if ($peertubelib->testConnection()) {
        Feedback::success(tr("PeerTube API connection successful."));
    } else {
        Feedback::error(tr("Unable to connect to PeerTube or validate its settings. Please check the service URL and credentials in the PeerTube settings section."));
    }
} else {
    Feedback::error(tr("PeerTube feature disabled"));
}

$smarty->assign('kcwText', $kcwText);
$smarty->assign('kplayerlist', $kplayerlist);
$smarty->assign('peertubeText', $peertubeText);
$smarty->assign('peertubePlayerList', $peertubePlayerList);
