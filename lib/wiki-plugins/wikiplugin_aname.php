<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_aname_info()
{
    return [
        'name' => tra('Anchor Name'),
        'documentation' => 'PluginAname',
        'description' => tra('Create an anchor that can be linked to'),
        'prefs' => ['wikiplugin_aname'],
        'body' => tra('The name of the anchor.'),
        'tags' => [ 'basic' ],
        'introduced' => 1,
        'params' => [],
        'iconname' => 'link',
        'format' => 'html',
    ];
}

function wikiplugin_aname($data, $params)
{
    global $tikilib;
    extract($params, EXTR_SKIP);

    $data = $tikilib->attValue($data);

    return "<a id=" . $data . "></a>";
}
