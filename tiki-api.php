<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * API front controller
 * Handle authentication, input/output serialization and send for service broken processing.
 */

define('TIKI_API', true);

// Tiki uses SESSION internally a lot but API shouldn't allow initializing the session from a cookie
// API requests are stateless and also we turn off CSRF protection
ini_set('session.use_cookies', 0);

require_once('tiki-setup.php');

if ($prefs['auth_api_tokens'] !== 'y') {
    TikiLib::lib('access')->display_error('API', 'Service not enabled.', 403);
}

if (! isset($_REQUEST['controller'])) {
    TikiLib::lib('access')->display_error('API', 'Endpoint not found.', 404);
}

if (empty($user)) {
    // disallow anonymous access for now
    // we need to make sure all services impose proper permissions before allowing anoymous access
    TikiLib::lib('access')->display_error('API', 'Unauthorized.', 401);
}

$controller = $_REQUEST['controller'];
$extensionPackage = '';

if (strpos($_REQUEST['controller'], ".") !== false) {
    $parts = explode(".", $_REQUEST['controller']);
    if (count($parts) == 3) {
        $extensionPackage = $parts[0] . "." . $parts[1];
        $controller = $parts[2];
    }
}

$action = $_REQUEST['action'];

$broker = TikiLib::lib('service')->getBroker($extensionPackage);
$broker->process($controller, $action, $jitRequest);
