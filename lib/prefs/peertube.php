<?php
function prefs_peertube_list()
{
    return [
        'peertube_service_url' => [
            'name' => tra('PeerTube service URL'),
            'description' => tra('For example, https://videos.evoludata.com/'),
            'type' => 'text',
            'size' => 40,
            'default' => 'https://videos.evoludata.com/',
            'tags' => ['basic'],
        ],
        'peertube_client_id' => [
            'name' => tra('Client ID'),
            'description' => tra('PeerTube OAuth client ID'),
            'type' => 'text',
            'size' => 45,
            'filter' => 'alnum',
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_client_secret' => [
            'name' => tra('Client secret'),
            'description' => tra('PeerTube OAuth client secret'),
            'type' => 'text',
            'size' => 45,
            'filter' => 'alnum',
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_username' => [
            'name' => tra('Username'),
            'description' => tra('PeerTube username for video uploads'),
            'type' => 'text',
            'size' => 45,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_password' => [
            'name' => tra('Password'),
            'description' => tra('PeerTube password for video uploads'),
            'type' => 'password',
            'size' => 45,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_channel_id' => [
            'name' => tra('Channel ID'),
            'description' => tra('PeerTube channel ID for video uploads'),
            'type' => 'text',
            'size' => 45,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_player_id' => [
            'name' => tra('PeerTube player ID'),
            'description' => tra('PeerTube player configuration ID (if applicable)'),
            'type' => 'text',
            'size' => 20,
            'default' => '',
            'tags' => ['basic'],
        ],
    ];
}
