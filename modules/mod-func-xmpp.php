<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_xmpp_info()
{
    return [
        'description' => tra('Hold a chat session using XMPP (uses the ConverseJS client).'),
        'name' => tra('XMPP'),
        'params' => [
            'show_controlbox_by_default' => [
                'name' => tra('Show controlbox on load'),
                'description' => tra('If controlbox should be shown after page load'),
                'default' => 'n',
                'filter' => 'alpha',
            ],
        ],
        'prefs' => ['xmpp_feature'],
        'title' => tra('XMPP'),
        'type' => 'function'
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_xmpp($mod_reference, &$module_params)
{
    global $user, $prefs;

    if (($prefs['xmpp_chat_button'] ?? 'y') !== 'y') {
        return;
    }

    if (empty($user) && ! isset($module_params['anonymous'])) {
        $module_params['anonymous'] = 'y';
    }

    TikiLib::lib('xmpp')->render_xmpp_client($module_params);
}
