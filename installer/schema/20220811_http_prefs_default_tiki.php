<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
use Tiki\Installer\Installer;

/**
 * Set the default value on upgraded Tikis for http_header_frame_options,
 * http_header_xss_protection, http_header_content_type_options, http_header_content_security_policy,
 * http_header_strict_transport_security and http_header_public_key_pins to 'n'
 *
 * @param Installer $installer
 */
function upgrade_20220811_http_prefs_default_tiki($installer)
{
    $installer->preservePreferenceDefault('http_header_frame_options', 'n');
    $installer->preservePreferenceDefault('http_header_xss_protection', 'n');
    $installer->preservePreferenceDefault('http_header_content_type_options', 'n');
    $installer->preservePreferenceDefault('http_header_content_security_policy', 'n');
    $installer->preservePreferenceDefault('http_header_strict_transport_security', 'n');
    $installer->preservePreferenceDefault('http_header_public_key_pins', 'n');
}
