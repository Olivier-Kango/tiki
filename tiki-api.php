<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
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

$bridge = new Services_ApiBridge($jitRequest);
$bridge->handle();
