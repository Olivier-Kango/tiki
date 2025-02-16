<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_TrackerFieldSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_tracker_fields')->fetchColumn('fieldId', [
            'type' => $this->db->table('tiki_tracker_fields')->notIn(['h'])
        ]);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $lib = TikiLib::lib('trk');

        $field = $lib->get_tracker_field($objectId, false);

        if (! $field || $field['type'] === 'h') {
            return false;
        }

        if ($prefs['unified_exclude_nonsearchable_fields'] === 'y' && $field['isSearchable'] !== 'y') {
            return false;
        }

        if (isset($field['permName']) && strlen($field['permName']) > Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE) {
            Feedback::error(tr(
                'Object "%0" (type %1) was not indexed because its "Permanent name" contains more than %2 characters. It\'s recommended to change its value.',
                $objectId,
                'trackerfield',
                Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE
            ));

            return false;
        }

        $trackername = tr('unknown');
        if ($definition = Tracker_Definition::get($field['trackerId'])) {
            $trackername = $definition->getConfiguration('name');
        }

        $data = [
            'title' => $typeFactory->sortable($field['name']),
            'description' => $typeFactory->plaintext($field['description']),
            'tracker_id' => $typeFactory->identifier($field['trackerId']),
            'tracker_name' => $typeFactory->sortable($trackername),
            'position' => $typeFactory->numeric($field['position']),
            'permName' => $typeFactory->identifier($field['permName']),

            'searchable' => $typeFactory->identifier('n'),

            'view_permission' => $typeFactory->identifier('tiki_p_view_trackers'),
        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'description',
            'tracker_id',
            'tracker_name',
            'position',
            'permName',

            'searchable',

            'view_permission',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'description' => 'plaintext',
            'tracker_id' => 'identifier',
            'tracker_name' => 'sortable',
            'position' => 'numeric',
            'permName' => 'identifier',

            'searchable' => 'identifier',

            'view_permission' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
        ];
    }
}
