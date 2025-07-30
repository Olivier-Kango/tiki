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
            'list' => 'string',               // GET parameter for list type
            'action' => 'string',             // GET parameter for action to perform
            'videoId' => 'string',            // GET parameter for video UUID (PeerTube uses UUIDs)
            'view' => 'string',               // GET parameter for view mode
        ],
    ],
];
require_once 'tiki-setup.php';
$access->check_feature('feature_peertube');
$access->check_permission(['tiki_p_list_peertube']);

$mediaTypeAsString['1'] = 'Video'; // PeerTube primarily handles videos, no images or audio natively

// Define possible statuses (adapted for PeerTube based on API documentation)
$statusAsString = [
    0 => tra('Waiting for publication'),
    1 => tra('Published'),
    2 => tra('To transcode'),
    3 => tra('Transcoding'),
    4 => tra('Transcoding failed'),
    5 => tra('Deleted'),
];

if (! isset($_REQUEST['list'])) {
    $_REQUEST['list'] = 'videos';
}

try {
    TikiLib::lib('access')->setTicket();

    if (isset($_REQUEST['action'])) {
        $videoId = [];

        if (! empty($_REQUEST['videoId'])) {
            if (is_array($_REQUEST['videoId'])) {
                $videoId = $_REQUEST['videoId'];
            } else {
                $videoId[0] = $_REQUEST['videoId'];
            }
        }

        switch ($_REQUEST['action']) {
            case 'Delete':
                $access->check_permission(['tiki_p_delete_peertube']);
                $access->checkCsrf();
                $peertubelib = TikiLib::lib('peertubeuser');

                foreach ($videoId as $vi) {
                    $peertubelib->deleteVideo($vi);
                }
                Feedback::success(tra('Video deleted successfully.'));
                header('Location: tiki-list_peertube_entries.php?list=videos');
                die;
            default:
                Feedback::errorAndDie(tra('Invalid action'), \Laminas\Http\Response::STATUS_CODE_409);
        }
    }

    $sort_mode = $jitRequest->sort_mode->word() ?: 'desc_publishedAt';
    $smarty->assign_by_ref('sort_mode', $sort_mode);
    $sort_mode = preg_replace(['/desc_/', '/asc_/'], ['-', '+'], $sort_mode, 1);
    $sort_mode = preg_replace('/-+/', '-', $sort_mode);
    $sort_mode = trim($sort_mode, '-+');
    if (! preg_match('/^[+-][a-zA-Z0-9_]+$/', $sort_mode)) {
        $sort_mode = '-publishedAt';
    }

    $find = $jitRequest->find->text();
    $smarty->assign('find', $find);

    $page_size = $jitRequest->maxRecords->int() ?: $prefs['maxRecords']; // Number of items per page
    $offset = max(0, $jitRequest->offset->int()); // Offset for pagination
    $page = (int)(($offset / $page_size) + 1); // Current page number (cast to int)

    if ($_REQUEST['list'] == 'videos') {
        $peertubelib = TikiLib::lib('peertubeuser');
        $videolist = $peertubelib->listVideos($sort_mode, $page, $page_size, $find); // Call list method from PeerTubeLib

        if ($videolist && isset($videolist->data) && is_array($videolist->data)) {
            if ($jitRequest->view->alpha() != 'browse') {
                foreach ($videolist->data as &$video) {
                    if (is_array($video)) {
                        $video['mediaType'] = $mediaTypeAsString[1];
                        $state_id = isset($video['state']) && is_array($video['state']) ? ($video['state']['id'] ?? null) : null;
                        $video['statusString'] = $state_id !== null ? ($statusAsString[$state_id] ?? tra('Unknown')) : tra('Unknown');
                    }
                }
                unset($video);
            }
            $smarty->assign('klist', $videolist->data);
            $smarty->assign('count', $videolist->total ?? count($videolist->data));
        } else {
            $smarty->assign('klist', []);
            $smarty->assign('count', 0);
        }
        $smarty->assign('entryType', 'videos');
        $smarty->assign('view', $jitRequest->view->alpha());
    }

    $smarty->assign('offset', $offset);
    $smarty->assign('maxRecords', $page_size);
    $smarty->assign('mid', 'tiki-list_peertube_entries.tpl');
    $smarty->assign('ticket', TikiLib::lib('access')->getTicket());
    $smarty->display('tiki.tpl');
} catch (Exception $e) {
    $access->display_error('', tra('Communication error'), 500, true, tra('Invalid response provided by the PeerTube server. Please retry.') . '<br /><em>' . $e->getMessage() . '</em>');
}
