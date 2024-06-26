<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [[
        'staticKeyFilters'  => [
            'itemId'        => 'int',
            'fieldId'       => 'int',
            'version'       => 'int',
            'offset'        => 'int',
            'diff_style'    => 'word',
        ]],
            ['catchAllUnset' => null],
];
$section = 'trackers';
require_once('tiki-setup.php');

$broker = TikiLib::lib('service')->getBroker();
$broker->process('tracker', 'item_history', $jitRequest);
