<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_bigbluebutton_info()
{
    return [
        'name' => tra('BigBlueButton'),
        'documentation' => 'PluginBigBlueButton',
        'description' => tra('Hold a video/audio/chat/presentation session using the BigBlueButton web conferencing system.'),
        'format' => 'html',
        'prefs' => [ 'wikiplugin_bigbluebutton', 'bigbluebutton_feature' ],
        'iconname' => 'video',
        'introduced' => 5,
        'tags' => [ 'basic' ],
        'params' => [
            'name' => [
                'required' => true,
                'name' => tra('Meeting'),
                'description' => tr('MeetingID for BigBlueButton. This is a 5 digit number, starting with a 7.
                    Ex.: %0 or %1.', '<code>77777</code>', '<code>71111</code>'),
                'since' => '5.0',
                'filter' => 'text',
                'default' => '',
            ],
            'prefix' => [
                'required' => false,
                'name' => tra('Anonymous Prefix'),
                'description' => tra('Unregistered users will get this token prepended to their name.'),
                'since' => '5.0',
                'filter' => 'text',
                'default' => '',
            ],
            'welcome' => [
                'required' => false,
                'name' => tra('Welcome Message'),
                'description' => tra('A message to be provided when someone enters the room.'),
                'since' => '5.0',
                'filter' => 'text',
                'default' => '',
            ],
            'number' => [
                'required' => false,
                'name' => tra('Dial Number'),
                'description' => tra('The phone-in support number to join from traditional phones.'),
                'since' => '5.0',
                'filter' => 'text',
                'default' => '',
            ],
            'voicebridge' => [
                'required' => false,
                'name' => tra('Voice Bridge'),
                'description' => tra('Code to enter for phone attendees to join the room. Typically, the same 5 digits
                    of the MeetingID.'),
                'since' => '5.0',
                'filter' => 'digits',
                'default' => '',
            ],
            'logout' => [
                'required' => false,
                'name' => tra('Log-out URL'),
                'description' => tra('URL to which the user will be redirected after logging out of BigBlueButton.'),
                'since' => '5.0',
                'filter' => 'url',
                'default' => '',
            ],
            'recording' => [
                'required' => false,
                'name' => tra('Record'),
                'description' => tra('The recording starts when the first person enters the room, and ends when the last
                    person leaves. After a period of processing (which depends on the length of the meeting), the
                    recording will be added to the list of all recordings for this room. Requires BBB >= 0.8.'),
                'since' => '5.0',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['value' => 0, 'text' => tr('Off')],
                    ['value' => 1, 'text' => tr('On')],
                ],
            ],
            'showrecording' => [
                'required' => false,
                'name' => tra('Display Recordings'),
                'description' => tra('Enable or Disable the display of video recordings.'),
                'filter' => 'alpha',
                'default' => 'y',
            ],
            'showattendees' => [
                'required' => false,
                'name' => tra('Display Attendees'),
                'description' => tra('Enable or Disable the display of attendees list.'),
                'filter' => 'alpha',
                'default' => 'y',
            ],
        ],
    ];
}

function wikiplugin_bigbluebutton($data, $params)
{
    try {
        global $prefs, $user;

        if (empty($prefs['bigbluebutton_server_location'])) {
            return WikiParser_PluginOutput::error(tr('Warning'), tr('BigBlueButton server location is not defined'));
        }

        $bigbluebuttonlib = TikiLib::lib('bigbluebutton');
        $meeting = $params['name']; // Meeting is more descriptive than name, but parameter name was already decided.
        $smarty = TikiLib::lib('smarty');
        $smarty->assign('bbb_meeting', $meeting);

        $perms = Perms::get('bigbluebutton', $meeting);

        $params = array_merge(['prefix' => '', 'recording' => 0], $params);
        // This is incomplete, will only apply if the dynamic feature is enabled. To be completed.
        $params['configuration'] = [
            'presentation' => [
                'active' => false,
            ],
        ];
        $smarty->assign('bbb_params', Tiki_Security::get()->encode($params));

        if (! $bigbluebuttonlib->roomExists($meeting)) {
            if (! isset($_POST['bbb']) || $_POST['bbb'] != $meeting || ! $perms->bigbluebutton_create) {
                if (
                    $perms->bigbluebutton_view_rec
                        && isset($params['showrecording'])
                        && $params['showrecording'] != 'n'
                ) {
                    $smarty->assign('bbb_recordings', $bigbluebuttonlib->getRecordings($meeting));
                } else {
                    $smarty->assign('bbb_recordings', null);
                }
                return $smarty->fetch('wiki-plugins/wikiplugin_bigbluebutton_create.tpl');
            }
        }

        if ($perms->bigbluebutton_view_rec) {
            $smarty->assign('bbb_recordings', $bigbluebuttonlib->getRecordings($meeting));
        } else {
            $smarty->assign('bbb_recordings', null);
        }

        if ($perms->bigbluebutton_join) {
            if ($params['showattendees'] != 'n') {
                $smarty->assign('bbb_attendees', $bigbluebuttonlib->getAttendees($meeting));
                $smarty->assign('bbb_show_attendees', true);
            } else {
                $smarty->assign('bbb_show_attendees', false);
            }

            return $smarty->fetch('wiki-plugins/wikiplugin_bigbluebutton.tpl');
        }

        // Won't display anything if recordings were not loaded
        return $smarty->fetch('wiki-plugins/wikiplugin_bigbluebutton_view_recordings.tpl');
    } catch (Exception $e) {
        return WikiParser_PluginOutput::internalError(tr('BigBlueButton is misconfigured or inaccessible.'));
    }
}
