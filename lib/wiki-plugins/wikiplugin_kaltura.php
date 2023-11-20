<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_kaltura_info()
{
    global $prefs;
    $players = [];
    if ($prefs['feature_kaltura'] === 'y') {
        $kalturaadminlib = TikiLib::lib('kalturaadmin');

        $playerList = $kalturaadminlib->getPlayersUiConfs();
        foreach ($playerList as $pl) {
            $players[] = ['value' => $pl['id'], 'text' => tra($pl['name'])];
        }

        if (count($players)) {
            array_unshift($players, ['value' => '', 'text' => tra('Default')]);
        }
    }

    return [
        'name' => tra('Kaltura Video'),
        'documentation' => 'PluginKaltura',
        'description' => tra('Display a video created through the Kaltura feature'),
        'prefs' => ['wikiplugin_kaltura', 'feature_kaltura'],
        'format' => 'html',
        'iconname' => 'video',
        'introduced' => 4,
        'params' => [
            'id' => [
                'required' => true,
                'name' => tra('Kaltura Entry ID'),
                'description' => tra('Kaltura ID of the video to be displayed'),
                'since' => '4.0',
                'tags' => ['basic'],
                'area' => 'kaltura_uploader_id',
                'type' => 'kaltura',
                'iconname' => 'video',
            ],
            'player_id' => [
                'required' => false,
                'name' => tra('Kaltura Video Player ID'),
                'description' => tra('Kaltura Dynamic Player (KDP) user interface configuration ID'),
                'since' => '10.0',
                'type' => empty($players) ? 'text' : 'list',
                'options' => $players,
                'size' => 20,
                'default' => '',
                'tags' => ['basic'],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width of the player in pixels or percent'),
                'since' => '10.0',
                'default' => 595,
                'filter' => 'text',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height of the player in pixels or percent'),
                'since' => '10.0',
                'default' => 365,
                'filter' => 'text',
            ],
            'align' => [
                'required' => false,
                'name' => tra('Align'),
                'description' => tra('Alignment of the player'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Not set'), 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Centre'), 'value' => 'center'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ],
            'float' => [
                'required' => false,
                'name' => tra('Float'),
                'description' => tra('Alignment of the player using CSS float'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Not set'), 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ]
        ],
    ];
}

function wikiplugin_kaltura($data, $params)
{
    global $prefs, $user, $page;

    static $instance = 0;

    $instance++;

    $defaults = [];
    $plugininfo = wikiplugin_kaltura_info();
    foreach ($plugininfo['params'] as $key => $param) {
        if (isset($param['default'])) {
            $defaults[$key] = $param['default'];
        }
    }

    if (empty($params['id'])) {
        $html = '<span class="alert-warning">' . tra('Media ID is required to display the video') . '</span>';
        return $html;
    }

    if (empty($params['player_id'])) {
        $params['player_id'] = $prefs['kaltura_kdpUIConf'];
    }

    if (empty($params['width']) || empty($params['height'])) {
        $kalturaadminlib = TikiLib::lib('kalturaadmin');
        $player = $kalturaadminlib->getPlayersUiConf($params['player_id']);
        if (! empty($player)) {
            if (empty($params['width'])) {
                $params['width'] = $player['width'];
            }
            if (empty($params['height'])) {
                $params['height'] = $player['height'];
            }
        } else {
            return '<span class="alert-warning">' . tra('Player not found') . '</span>';
        }
    }

    $kalturalib = TikiLib::lib('kalturauser');
    $params = array_merge($defaults, $params);
    $params['session'] = $kalturalib->getSessionKey();
    $params['media_url'] = $kalturalib->getMediaUrl($params['id'], $params['player_id']);

    try {
        $playlistObject = $kalturalib->getPlaylist($params['id']);
    } catch (Exception $e) {
        $playlistObject = null;
    }

    $style = '';
    if (! empty($params['align'])) {
        $style .= "text-align:{$params['align']};";
    }
    if (! empty($params['float'])) {
        $style .= "float:{$params['float']};";
    }

    $embedIframeJs = '/embedIframeJs';  // TODO add as params?
    $leadWithHTML5 = 'true';
    $autoPlay = 'false';

    if ($playlistObject) {
        parse_str(str_replace(['k_pl_0_u', 'k_pl_0_n'], ['kpl0U', 'kpl0N'], $playlistObject->executeUrl), $playlistAPI);
        $playlistAPI['kpl0Id'] = $params['id'];
        $playlistAPI = '"playlistAPI": ' . json_encode($playlistAPI);
    } else {
        $playlistAPI = '';
    }

    TikiLib::lib('header')
        ->add_jsfile_cdn("{$prefs['kaltura_kServiceUrl']}/p/{$prefs['kaltura_partnerId']}/sp/{$prefs['kaltura_partnerId']}00{$embedIframeJs}/uiconf_id/{$params['player_id']}/partner_id/{$prefs['kaltura_partnerId']}")
        ->add_jq_onready(
            "
mw.setConfig('Kaltura.LeadWithHTML5', $leadWithHTML5);

kWidget.embed({
    targetId: 'kaltura_player$instance',
    wid: '_{$prefs['kaltura_partnerId']}',
    uiconf_id: '{$params['player_id']}',
    entry_id: '{$params['id']}',
    flashvars: { // flashvars allows you to set runtime uiVar configuration overrides.
        //autoPlay: $autoPlay
        $playlistAPI
    },
    params: { // params allows you to set flash embed params such as wmode, allowFullScreen etc
        wmode: 'transparent'
    },
    readyCallback: function (playerId) {
        \$ = \$jq;    // restore our jQuery after Kaltura has finished with it
        console.log('Player:' + playerId + ' is ready ');
    }
});"
        );
    if (is_numeric($params['width'])) {
        $params['width'] .= 'px';
    }
    if (is_numeric($params['height'])) {
        $params['height'] .= 'px';
    }
    return "<div id='kaltura_player$instance' style='width:{$params['width']};height:{$params['height']};$style'></div>";
}
