<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_TrackerSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_trackers')->fetchColumn('trackerId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $lib = TikiLib::lib('trk');

        $tracker = $lib->get_tracker($objectId);

        if (! $tracker) {
            return false;
        }

        $data = [
            'title' => $typeFactory->sortable($tracker['name']),
            'modification_date' => $typeFactory->timestamp($tracker['lastModif']),
            'creation_date' => $typeFactory->timestamp($tracker['created']),
            'date' => $typeFactory->timestamp($tracker['created']),
            'description' => $typeFactory->plaintext($tracker['description']),

            'searchable' => $typeFactory->identifier('n'),

            'view_permission' => $typeFactory->identifier('tiki_p_view_trackers'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'modification_date',
            'creation_date',
            'date',
            'description',

            'searchable',

            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'modification_date' => 'timestamp',
            'creation_date' => 'timestamp',
            'date' => 'timestamp',
            'description' => 'plaintext',

            'searchable' => 'identifier',

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
