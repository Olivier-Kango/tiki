<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param $installer
 */
function post_20090416_plugin_security_tiki($installer)
{
    $result = $installer->query("SELECT value FROM tiki_preferences WHERE name = 'plugin_fingerprints'");
    if ($row = $result->fetchRow()) {
        $data = unserialize($row['value']);

        foreach ($data as $fingerprint => $string) {
            list($status, $timestamp, $user) = explode('/', $string);
            $installer->query(
                "INSERT INTO tiki_plugin_security (fingerprint, status, approval_by, last_update, last_objectType, last_objectId) VALUES(?, ?, ?, ?, '', '')",
                [$fingerprint, $status, $user, $timestamp]
            );
        }

        $installer->query("DELETE FROM tiki_preferences WHERE name = 'plugin_fingerprints'");
    }
}
