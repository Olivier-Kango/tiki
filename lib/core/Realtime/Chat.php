<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Realtime;

use Ratchet\ConnectionInterface;

/**
 * Chat server: DEMO!
 * Send any incoming messages to all connected clients (except sender)
 */
class Chat extends SessionAwareApp
{
    public function onMessage(ConnectionInterface $from, $msg)
    {
        parent::onMessage($from, $msg);

        foreach ($this->clients as $client) {
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }
}
