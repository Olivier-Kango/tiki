<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'videoId' => 'int',              // post
            'action' => 'word',              // post
            'name' => 'word',                // post
            'description' => 'xss',          // post
            'update' => 'bool',              // post
            'tags' => 'word',                // post
        ],
    ],
];
require_once 'tiki-setup.php';

$access->check_feature('feature_peertube');

try {
    $videoId = [];
    if (! empty($_REQUEST['videoId'])) {
        if (is_array($_REQUEST['videoId'])) {
            $videoId = $_REQUEST['videoId'];
        } else {
            $videoId[0] = $_REQUEST['videoId'];
        }
    }

    $mode = null;
    if (! empty($videoId) && isset($_REQUEST['action'])) {
        $mode = $_REQUEST['action'];
        $smarty->assign('pmode', $mode);

        switch ($mode) {
            case 'delete':
                $access->check_permission(['tiki_p_delete_peertube']);
                $access->checkCsrf();
                $peertubelib = TikiLib::lib('peertubeuser');
                foreach ($videoId as $vi) {
                    $peertubelib->deleteVideo($vi);
                }
                header('Location: tiki-list_peertube_entries.php');
                exit;

            case 'edit':
                $access->check_permission(['tiki_p_edit_peertube']);
                $peertubelib = TikiLib::lib('peertubeuser');
                $video = $peertubelib->getVideo($videoId[0]);
                if ($_REQUEST['update']) {
                    $peertubelib->updateVideo(
                        $videoId[0],
                        [
                            'name' => $_REQUEST['name'],
                            'description' => $_REQUEST['description'],
                            'tags' => $_REQUEST['tags'],
                        ]
                    );
                    header('Location: tiki-peertube_video.php?videoId=' . $videoId[0]);
                    exit;
                }
                $smarty->assign('videoId', $videoId[0]);
                $smarty->assign('videoInfo', $video);
                break;

            case 'default':
                Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
        }
    } else {
        if (isset($videoId[0])) {
            $access->check_permission(['tiki_p_view_peertube']);
            $smarty->assign('pmode', 'view');
            $peertubelib = TikiLib::lib('peertubeuser');
            $video = $peertubelib->getVideo($videoId[0]);
            $smarty->assign('videoId', $videoId[0]);
            $smarty->assign('videoInfo', $video);
        }
    }

    $smarty->assign('mid', 'tiki-peertube_video.tpl');
    $smarty->display('tiki.tpl');
} catch (Exception $e) {
    $access->display_error(
        '',
        tr('Communication error'),
        500,
        true,
        tr('Invalid response provided by the PeerTube server. Please retry.') . '<br /><em>' . $e->getMessage() . '</em>'
    );
}
