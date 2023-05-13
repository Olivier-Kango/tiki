<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Preserve the default url scheme pref as the default changed since 16.x
 *
 * @param Installer $installer
 */
function upgrade_20170702_wiki_url_scheme_pref_default_tiki($installer)
{
    $installer->preservePreferenceDefault('wiki_url_scheme', 'urlencode');
}
