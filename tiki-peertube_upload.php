<?php
require_once 'tiki-setup.php';

$access = TikiLib::lib('access');
$access->check_feature('feature_peertube');
$access->check_permission('tiki_p_upload_peertube');

$peertubelib = TikiLib::lib('peertubeuser');
$smarty = TikiLib::lib('smarty');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! empty($_FILES['video'])) {
    if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = tra('File upload error: ') . $_FILES['video']['error'];
    } else {
        $metadata = [
            'name' => $_POST['name'] ?? 'Untitled Video',
            'description' => $_POST['description'] ?? '',
            'channelId' => $prefs['peertube_channel_id'],
        ];

        // Prepare file for API upload
        $file = [
            'videofile' => curl_file_create(
                $_FILES['video']['tmp_name'],
                $_FILES['video']['type'],
                $_FILES['video']['name']
            ),
        ];

        // Upload to PeerTube
        try {
            $result = $peertubelib->uploadVideo($file, $metadata);
            if (! empty($result['video']['uuid'])) {
                $success = tra('Video uploaded successfully!');
                // Optionally store video metadata in Tiki's database or tracker
                header('Location: tiki-peertube_video.php?videoId=' . urlencode($result['video']['uuid']));
                exit;
            } else {
                $errors[] = tra('Failed to upload video to PeerTube.');
            }
        } catch (Exception $e) {
            $errors[] = tra('Error during upload: ') . $e->getMessage();
        }
    }
}


$smarty->assign('errors', $errors);
$smarty->assign('success', $success);
$smarty->assign('mid', 'tiki-peertube_upload.tpl');
$smarty->display('tiki.tpl');
