<?php

namespace Tiki\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class XmppUserSyncSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'tiki.user.groupjoin' => 'onUserGroupJoin',
            'tiki.user.groupleave' => 'onUserGroupLeave',
        ];
    }

    public function onUserGroupJoin($data): void
    {
        $user = $this->extractUser($data);
        if ($user === '') {
            return;
        }

        $this->markPendingSync($user);
    }

    public function onUserGroupLeave($data): void
    {
        $user = $this->extractUser($data);
        if ($user === '') {
            return;
        }

        $this->markPendingSync($user);
    }

    private function extractUser($data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            return (string) ($data['user'] ?? $data['username'] ?? $data['object'] ?? '');
        }

        if (is_object($data) && method_exists($data, 'getUsername')) {
            return (string) $data->getUsername();
        }

        return '';
    }

    private function markPendingSync(string $user): void
    {
        static $running = [];

        if (isset($running[$user])) {
            return;
        }

        $running[$user] = true;

        global $prefs;

        if (($prefs['xmpp_feature'] ?? 'n') !== 'y') {
            unset($running[$user]);
            return;
        }

        try {
            $xmpplib = \TikiLib::lib('xmpp');
            if (method_exists($xmpplib, 'markUserXmppSyncNeeded')) {
                $xmpplib->markUserXmppSyncNeeded($user);
            }
        } catch (\Throwable $e) {
            // Handle sync error silently
        }

        unset($running[$user]);
    }
}
