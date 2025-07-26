<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'lib/wiki-plugins/wikiplugin_oembed.php';

function wikiplugin_peertube_info()
{
    return [
        'name' => tra('PeerTube Video'),
        'documentation' => 'PluginPeerTube',
        'description' => tra('Display a video hosted on a PeerTube instance'),
        'prefs' => ['wikiplugin_peertube', 'feature_peertube'],
        'format' => 'html',
        'iconname' => 'video',
        'params' => [
            'id' => [
                'required' => true,
                'name' => tra('PeerTube Video ID'),
                'description' => tra('PeerTube UUID of the video to be displayed'),
                'type' => 'text',
                'area' => 'peertube_uploader_id',
                'tags' => ['basic'],
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels (e.g., 560). Leave empty for responsive design.'),
                'default' => '',
                'filter' => 'digits',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels (e.g., 315). Leave empty for responsive design.'),
                'default' => '',
                'filter' => 'digits',
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start time'),
                'description' => tra('Start time offset in seconds'),
                'filter' => 'digits',
                'default' => 0,
            ],
            'privacyEnhanced' => [
                'required' => false,
                'name' => tra('Privacy-Enhanced'),
                'description' => tra('Enable privacy-enhanced mode (if applicable)'),
                'default' => '',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'bg' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Container background color (e.g., #ffffff, rgb(255, 255, 255), white)'),
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'border' => [
                'required' => false,
                'name' => tra('Borders'),
                'description' => tra('Container border color (e.g., #ffffff, rgb(255, 255, 255), white)'),
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners to the container (default: Yes)'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Allow full-screen'),
                'description' => tra('Enlarge video to full screen size'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
        ],
    ];
}

function wikiplugin_peertube($data, $params)
{
    global $prefs;

    if (empty($params['id'])) {
        return '<span class="alert-warning">' . tra('Video ID is required to display the video') . '</span>';
    }

    $url = rtrim($prefs['peertube_service_url'], '/') . '/videos/watch/' . $params['id'];

    $oembedParams = [
        'url' => $url,
        'width' => $params['width'] ?? '',
        'height' => $params['height'] ?? '',
        'start' => $params['start'] ?? 0,
        'privacyEnhanced' => $params['privacyEnhanced'] ?? '',
        'bg' => $params['bg'] ?? '',
        'border' => $params['border'] ?? '',
        'borderRadius' => $params['borderRadius'] ?? 'y',
        'allowFullScreen' => $params['allowFullScreen'] ?? 'y',
    ];

    // Call oEmbed plugin to handle embedding
    return wikiplugin_oembed('', $oembedParams);
}
