<?php

namespace Tiki\Realtime;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\Client;

class IotDashboardNotifier extends SessionAwareApp
{
    public function onOpen(ConnectionInterface $conn)
    {
        parent::onOpen($conn);
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParameters);
        $conn->appId = $queryParameters['app_id'] ?? null; // Initialize appId
        if ($conn->appId) {
            $conn->send(json_encode(['message' => tr("Successfully subscribed to broadcast for App with ID %0", $conn->appId)]));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (isset($data['app_id'])) {
            try {
                foreach ($this->clients as $client) {
                    if ($client->appId === $data['app_id'] && $from !== $client) {
                        $client->send(json_encode(['message' => $data['message']]));
                    }
                }
                if (isset($data['origin']) && $data['origin'] == "broadcast") {
                    $from->send(tr("Event broadcast successfully dispatched to all subscribed dashboards for AppID %0", $data['app_id']));
                }
            } catch (Exception $e) {
                $from->send($e->getMessage());
            }
        }
    }

    public static function broadCast(string $appId, array $message)
    {
        global $base_url;
        $data = json_encode(["origin" => "broadcast","app_id" => $appId,"message" => $message]);  // Data to be sent
        Client\connect(preg_replace('#http://#', 'ws://', preg_replace('#https://#', 'wss://', $base_url)) . 'ws/' . 'iot-dashboard-notifier?token=' . session_id())->then(function ($conn) use ($data, $appId) {
            $conn->on('message', function ($msg) use ($conn, $appId) {
                $table = new \TikiDb_Table(\TikiDb::get(), 'tiki_iot_apps_actions_logs');
                $table->insert(['app_uuid' => $appId,'action_message' => $msg]);
                $conn->close();
            });
            $conn->send($data);
        }, function ($e) use ($appId) {
            $text = tr("Websocket event broadcast did not succeed, connection failed: %0", $e->getMessage());
            $table = new \TikiDb_Table(\TikiDb::get(), 'tiki_iot_apps_actions_logs');
            $table->insert(['app_uuid' => $appId,'action_message' => $text]);
        });
    }
}
