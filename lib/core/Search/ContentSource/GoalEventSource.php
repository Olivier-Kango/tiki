<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_GoalEventSource implements Search_ContentSource_Interface
{
    private $table;

    public function __construct()
    {
        $this->table = TikiDb::get()->table('tiki_goal_events');
    }

    public function getDocuments()
    {
        return $this->table->fetchColumn('eventId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $event = $this->table->fetchRow(['eventType', 'eventDate', 'user', 'groups', 'targetType', 'targetObject'], [
            'eventId' => $objectId,
        ]);

        if ($event) {
            $target = null;
            if ($event['targetType'] && $event['targetObject']) {
                $target = "{$event['targetType']}:{$event['targetObject']}";
            }
            return [
                'modification_date' => $typeFactory->timestamp($event['eventDate']),
                'date' => $typeFactory->timestamp($event['eventDate']),
                'event_type' => $typeFactory->identifier($event['eventType']),
                'user' => $typeFactory->identifier($event['user']),
                'goal_groups' => $typeFactory->multivalue(json_decode($event['groups'], true)),
                'target' => $typeFactory->identifier($target),
            ];
        } else {
            return false;
        }
    }

    public function getProvidedFields(): array
    {
        return ['event_type', 'modification_date', 'user', 'goal_groups', 'target', 'date'];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'event_type' => 'identifier',
            'modification_date' => 'timestamp',
            'user' => 'identifier',
            'goal_groups' => 'multivalue',
            'target' => 'identifier',
            'date' => 'timestamp'
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'date' => true,
        ];
    }
}
