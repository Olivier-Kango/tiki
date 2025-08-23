<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TikiInit;

require_once 'tiki-setup.php';

$access = TikiLib::lib('access');
$access->check_feature('feature_peertube');
$access->check_permission('tiki_p_upload_videos');

require_once 'lib/videogals/peertubelib.php';

$peertubelib = new \Tiki\Videogals\PeerTubeLib();
$filegallib = TikiLib::lib('filegal');

$errors = [];

include('lib/Filegals/max_upload_size.php');

$channelList = [];
try {
    $channelResponse = $peertubelib->getMyChannels();
    if (! empty($channelResponse['data'])) {
        foreach ($channelResponse['data'] as $channel) {
            $id = $channel['id'];
            $name = $channel['displayName'] ?? $channel['name'];
            $channelList[$id] = $name;
        }
    }
} catch (Exception $e) {
    $errors[] = tra('Failed to load PeerTube channels: ') . $e->getMessage();
}

$smarty = TikiLib::lib('smarty');
$smarty->assign('channelList', $channelList);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['video'])) {
        if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = tra('File upload error: ') . $_FILES['video']['error'] . ' (Details: ' . print_r($_FILES['video'], true) . ')';
        } elseif ($_FILES['video']['size'] > $max_upload_size) {
            $errors[] = tra('File exceeds maximum size of ') . $max_upload_size_comment;
        } else {
            $metadata = [
                'name' => $_POST['name'] ?? 'Untitled Video',
                'description' => $_POST['description'] ?? '',
                'channelId' => intval($_POST['channelId']),
            ];

            if (! empty($_POST['privacy'])) {
                $metadata['privacy'] = intval($_POST['privacy']);
            }

            $file = [
                'videofile' => new \CURLFile(
                    $_FILES['video']['tmp_name'],
                    $_FILES['video']['type'],
                    $_FILES['video']['name']
                ),
            ];

            try {
                $result = $peertubelib->uploadVideo($file, $metadata);
                if (! empty($result['video']['uuid'])) {
                    Feedback::success(tra('Video uploaded successfully!'));
                    header('Location: tiki-peertube_video.php?videoId=' . urlencode($result['video']['shortUUID']));
                    exit;
                } else {
                    $errors[] = tra('Failed to upload video to PeerTube.');
                }
            } catch (Exception $e) {
                $errors[] = tra('Error during upload: ') . $e->getMessage();
            }
        }
    } else {
        $errors[] = tra('No file was uploaded. Please select a file and try again.');
    }
}

$smarty->assign('errors', $errors);
$smarty->assign('max_upload_size', $max_upload_size);
$smarty->assign('max_upload_size_comment', $max_upload_size_comment);
$smarty->assign('is_iis', TikiInit::isIIS());
$smarty->assign('mid', 'tiki-peertube_upload.tpl');
$smarty->display('tiki.tpl');
