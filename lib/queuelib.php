<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class QueueLib extends TikiDb_Bridge
{
    private $queue;
    private const QUEUE_GUARD_THRESHOLD = 2100000000;

    public function __construct()
    {
        $this->queue = $this->table('tiki_queue');
    }

     /**
     * Prevent pushing when AUTO_INCREMENT is near INT limit.
     * Automatically upgrade entryId to BIGINT if needed, or throw actionable error.
     */
    private function ensureQueueCapacity(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $db = TikiDb::get();

        $columnType = strtolower((string) $db->getOne("
            SELECT DATA_TYPE
              FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tiki_queue'
               AND COLUMN_NAME = 'entryId'
        "));

        if ($columnType !== 'int') {
            $done = true;
            return;
        }

        $ai = (int) $db->getOne("
            SELECT AUTO_INCREMENT
              FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tiki_queue'
        ");

        if ($ai >= self::QUEUE_GUARD_THRESHOLD) {
            $done = true;
            throw new \RuntimeException(
                tr('Indexing queue is nearing the INT AUTO_INCREMENT limit. '
                 . 'Run DB migrations (installer/schema/*.sql), then TRUNCATE TABLE `tiki_queue`, '
                 . 'and in Tiki go to Admin → Search → Rebuild Index.')
            );
        }
        $done = true;
    }

    public function pushAll($queue, array $messages)
    {
        $this->ensureQueueCapacity();
        foreach ($messages as $message) {
            $this->push($queue, $message);
        }
    }

    public function push($queue, array $message)
    {
        $this->ensureQueueCapacity();
        $this->queue->insert(
            [
                'queue' => $queue,
                'timestamp' => TikiLib::lib('tiki')->now,
                'message' => json_encode($message),
            ]
        );
    }

    public function clear($queue)
    {
        $this->queue->deleteMultiple(['queue' => $queue,]);
    }

    public function count($queue)
    {
        return $this->queue->fetchCount(['queue' => $queue,]);
    }

    public function pull($queue, $count = 1)
    {
        $handler = uniqid();

        // Mark entries as in processing
        $this->queue->updateMultiple(
            ['handler' => $handler],
            [
                'queue' => $queue,
                'handler' => null,
            ],
            $count
        );

        // Obtain the marked list
        $messages = $this->queue->fetchColumn('message', ['handler' => $handler,]);

        // Delete from the queue
        $this->queue->deleteMultiple(['handler' => $handler,]);

        // Strip duplicate messages
        $messages = array_unique($messages);
        if (count($messages)) {
            return array_map('json_decode', $messages, array_fill(0, count($messages), true));
        }

        return [];
    }
}
