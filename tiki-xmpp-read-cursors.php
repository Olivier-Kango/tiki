<?php

require_once 'tiki-setup.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user)) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$xmpplib = TikiLib::lib('xmpp');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $cursors = is_array($body) ? $body : [];
    echo json_encode($xmpplib->saveReadCursors($user, $cursors));
    exit;
}

echo json_encode($xmpplib->getReadCursors($user));
