<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

global $prefs, $base_url;
$userlib = TikiLib::lib('user');
$headerlib = TikiLib::lib('header');
$smarty = TikiLib::lib('smarty');

$headerlib->add_jsfile('lib/jquery_tiki/tiki-connect.js');

if (empty($prefs['connect_site_title'])) {
    $defaults = json_encode(
        [
            'connect_site_title' => $prefs['browsertitle'],
            'connect_site_email' => $userlib->get_admin_email(),
            'connect_site_url' => $base_url,
            'connect_site_keywords' => $prefs['metatag_keywords'],
            'connect_site_location' => $prefs['gmap_defaultx'] . ',' . $prefs['gmap_defaulty'] . ',' . $prefs['gmap_defaultz'],
        ]
    );

    $headerlib->add_jq_onready(
        <<<JQ
        $("#connect_defaults_btn a").on("click", function(){
            var connect_defaults = $defaults;
            for (var el in connect_defaults) {
                $("input[name=" + el + "]").val(connect_defaults[el]);
            }
            return false;
        });
JQ
    );
}

if ($prefs['connect_server_mode'] === 'y') {
    $connectlib = TikiLib::lib('connect_server');

    $search_str = '';

    if (isset($_REQUEST['cserver'])) {
        if ($_REQUEST['cserver'] === 'rebuild') {
            $connectlib->rebuildIndex();
        } elseif (! empty($_REQUEST['cserver_search'])) {
            $search_str = $_REQUEST['cserver_search'];
        }
    }
    $smarty->assign('cserver_search_text', $search_str);
    $receivedDataStats = $connectlib->getReceivedDataStats();
    $smarty->assign_by_ref('connect_stats', $receivedDataStats);
    $matchingConnections = $connectlib->getMatchingConnections($search_str);
    $smarty->assign_by_ref('connect_recent', $matchingConnections);
} else {
    $smarty->assign('connect_stats', null);
    $smarty->assign('connect_recent', null);
}

$smarty->assign('jitsi_url', Services_Suite_Controller::getJitsiUrl());
