<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$_SERVER['HTTP_USER_AGENT'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'type'                        => 'word',              //post
        ],
    ],
];
require_once('tiki-setup.php');

$_REQUEST['type'] = (! empty($_REQUEST['type']) ? $_REQUEST['type'] : 'html');

if ($_REQUEST['type'] == 'html') {
    $access->check_feature('feature_htmlfeed');
    $feed = new Feed_Html();
    print_r(json_encode($feed->feed()));
}
