<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'tiki-setup.php';

if (empty($user)) {
    http_response_code(401);
    exit;
}

$xmpplib = TikiLib::lib('xmpp');
$tikilib = TikiLib::lib('tiki');

// Atomic check for pending sync; skip if nothing to do
$pending = $tikilib->get_user_preference($user, 'xmpp_sync_pending', 'n');
if ($pending !== 'y') {
    http_response_code(204);
    exit;
}

// Mark as running to avoid concurrent executions
$tikilib->set_user_preference($user, 'xmpp_sync_pending', 'running');

try {
    if (method_exists($xmpplib, 'syncUserGroupsToXmpp')) {
        $xmpplib->syncUserGroupsToXmpp($user);
    }
    $tikilib->set_user_preference($user, 'xmpp_sync_pending', 'n');
    http_response_code(204);
} catch (\Throwable $e) {
    error_log('[XMPP] Deferred sync error for ' . $user . ': ' . $e->getMessage());
    // Allow retry on next call
    $tikilib->set_user_preference($user, 'xmpp_sync_pending', 'y');
    http_response_code(500);
}
