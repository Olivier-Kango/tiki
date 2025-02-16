<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
include_once('lib/directory/dirlib.php');
$access->check_feature('feature_directory');
$access->check_permission('tiki_p_view_directory');
if (! isset($_REQUEST['siteId'])) {
    $smarty->assign('msg', tra("No site indicated"));
    $smarty->display("error.tpl");
    die;
}

$site_info = $dirlib->dir_get_site($_REQUEST['siteId']);
if (! $site_info) {
    $smarty->assign('msg', tra("Site not found"));
    $smarty->display("error.tpl");
    die;
}

$url = $site_info['url'];
// Add a hit to the site
$dirlib->dir_add_site_hit($_REQUEST['siteId']);
// Redirect to the site URI
header("location: $url");
die;
