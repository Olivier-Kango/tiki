<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_CalendarSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_calendars')->fetchColumn('calendarId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        if (! TikiLib::lib('calendar')->calendarExists($objectId)) {
            trigger_error('Object calendar with Id : ' . $objectId . ' not found', E_USER_WARNING);
            return false;
        }
        $item = TikiLib::lib('calendar')->get_calendar($objectId);

        $data = [
            'title' => $typeFactory->sortable($item['name']),
            'creation_date' => $typeFactory->timestamp($item['created']),
            'modification_date' => $typeFactory->timestamp($item['lastmodif']),
            'date' => $typeFactory->timestamp($item['created']),
            'description' => $typeFactory->plaintext($item['description']),
            'language' => $typeFactory->identifier('unknown'),

            'personal' => $typeFactory->identifier($item['personal']),
            'user' => $typeFactory->identifier($item['user']),

            'view_permission' => $typeFactory->identifier('tiki_p_view_calendar'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'description',
            'language',
            'creation_date',
            'modification_date',
            'date',

            'personal',
            'user',

            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'description' => 'plaintext',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'date' => 'timestamp',

            'personal' => 'identifier',
            'user' => 'identifier',

            'view_permission' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
            'date' => true,
        ];
    }
}
