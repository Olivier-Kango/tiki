<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_FileSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getReferenceMap()
    {
        return [
            'gallery_id' => 'file_gallery',
        ];
    }

    public function getDocuments()
    {
        $files = $this->db->table('tiki_files');
        return $files->fetchColumn(
            'fileId',
            [
                'archiveId' => 0,
            ],
            -1,
            -1,
            'ASC'
        );
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $filegallib = TikiLib::lib('filegal');

        $file = $filegallib->get_file_info($objectId, true, false);

        if (! $file) {
            return false;
        }

        if (! empty($file['name'])) {
            // Many files when uploaded have underscore in the file name and makes search difficult
            $file['name'] = str_replace('_', ' ', $file['name']);
        }

        $data = [
            'title' => $typeFactory->sortable(empty($file['name']) ? $file['filename'] : $file['name']),
            'title_unstemmed' => $typeFactory->simpletext(empty($file['name']) ? $file['filename'] : $file['name']),
            'language' => $typeFactory->identifier('unknown'),
            'creation_date' => $typeFactory->timestamp($file['created']),
            'modification_date' => $typeFactory->timestamp($file['lastModif']),
            'date' => $typeFactory->timestamp($file['created']),
            'contributors' => $typeFactory->multivalue(array_unique([$file['author'], $file['user'], $file['lastModifUser']])),
            'description' => $typeFactory->plaintext($file['description']),
            'filename' => $typeFactory->identifier($file['filename']),
            'filetype' => $typeFactory->sortable(! empty($file['filetype']) ? preg_replace('/^([\w-]+)\/([\w-]+).*$/', '$1/$2', $file['filetype']) : ''),
            'filesize' => $typeFactory->plaintext($file['filesize']),
            'hits' => $typeFactory->numeric($file['hits']),

            'gallery_id' => $typeFactory->identifier($file['galleryId']),
            'file_comment' => $typeFactory->plaintext($file['comment']),
            'file_content' => $typeFactory->plaintext($file['search_data']),
            'ocr_content' => $typeFactory->plaintext($file['ocr_data']),

            'parent_object_type' => $typeFactory->identifier('file gallery'),
            'parent_object_id' => $typeFactory->identifier($file['galleryId']),
            'view_permission' => $typeFactory->identifier('tiki_p_download_files'),
        ];

        if ($prefs['fgal_enable_email_indexing'] === 'y' && $file['filetype'] == 'message/rfc822') {
            $file_object = Tiki\FileGallery\File::id($file['fileId']);
            $parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
            if ($parsed_fields) {
                $data += [
                    'email_subject' => $typeFactory->plaintext($parsed_fields['subject']),
                    'email_from' => $typeFactory->plaintext($parsed_fields['from']),
                    'email_sender' => $typeFactory->plaintext($parsed_fields['sender']),
                    'email_recipient' => $typeFactory->plaintext($parsed_fields['recipient']),
                    'email_date' => $typeFactory->timestamp($parsed_fields['date']),
                    'email_flags' => $typeFactory->multivalue(array_filter(array_values($parsed_fields['flags']))),
                    // readable in reports: multivalue is hashed by some engines
                    'email_flags_text' => $typeFactory->plaintext(implode(' ', array_filter(array_values($parsed_fields['flags'])))),
                    'email_content_type' => $typeFactory->plaintext($parsed_fields['content_type']),
                    'email_body' => $typeFactory->plainmediumtext($parsed_fields['body']),
                    'email_plaintext' => $typeFactory->plainmediumtext($parsed_fields['plaintext']),
                    'email_html' => $typeFactory->plainmediumtext($parsed_fields['html']),
                    'email_message_id' => $typeFactory->identifier(trim((string) $parsed_fields['message_id'], " \t<>")),
                ];
                $sources = TikiLib::lib('relation')->get_relations_to('file', $objectId, 'tiki.file.attach');
                foreach ($sources as $rel) {
                    if ($rel['type'] == 'trackeritem' && ! empty($rel['fieldId'])) {
                        $trackerId = TikiLib::lib('trk')->get_tracker_for_item($rel['itemId']);
                        $data['email_tracker_id'] = $typeFactory->identifier($trackerId);
                        $data['email_tracker_item_id'] = $typeFactory->identifier($rel['itemId']);
                        $data['email_tracker_field_id'] = $typeFactory->identifier($rel['fieldId']);
                        $data['email_folder'] = $typeFactory->identifier($this->getEmailFolder($trackerId, $rel['itemId'], $rel['fieldId'], $objectId));
                        $data = array_merge($data, $this->getTrackerItemPermissions($rel['itemId'], $rel['fieldId'], $typeFactory));
                        $view_path = 'tiki-webmail.php';
                        if (! empty($parsed_fields['source_id'])) {
                            $page_info = TikiLib::lib('tiki')->get_page_info_from_id($parsed_fields['source_id']);
                            if ($page_info && stristr($page_info['data'], "cypht")) {
                                $view_path = \SmartyTiki\Modifier\Sefurl::apply($page_info['pageName']);
                                if (preg_match("/tiki-index\.php\?page=.*/", $view_path)) {
                                    $view_path = "tiki-index.php?page_id=" . $parsed_fields['source_id'];
                                }
                            }
                        }
                        if (str_contains($view_path, '?')) {
                            $view_path .= '&';
                        } else {
                            $view_path .= '?';
                        }
                        $view_path .= "cypht_page=message&uid=" . $file['fileId'] . "&list_path=tracker_folder_" . $rel['itemId'] . "_" . $rel['fieldId'] . "&list_parent=tracker_" . $trackerId;
                        $data['url'] = $typeFactory->identifier($view_path);
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * An email filed in a tracker item is shown with the item (Email Folder field), not with the file gallery,
     * so it must be found by the same people who can view the item and that field.
     */
    private function getTrackerItemPermissions($itemId, $fieldId, Search_Type_Factory_Interface $typeFactory): array
    {
        $item = TikiLib::lib('trk')->get_tracker_item($itemId);
        if (empty($item)) {
            return [];
        }
        $itemObject = Tracker_Item::fromInfo($item);
        if (empty($itemObject) || ! $itemObject->getDefinition()) {
            return [];
        }
        $field = $itemObject->getDefinition()->getField($fieldId);
        if ($field && ($field['isHidden'] === 'y' || ! empty($field['visibleBy']))) {
            // restricted field, as in Tracker_Item::canViewField(): Admins, plus visibleBy groups that can view the item
            $groups = ['Admins'];
            if ($field['isHidden'] !== 'y') {
                $accessor = $itemObject->getPerms();
                $viewPermission = $itemObject->getViewPermission();
                foreach ($field['visibleBy'] as $group) {
                    $accessor->setGroups([$group]);
                    if ($accessor->$viewPermission) {
                        $groups[] = $group;
                    }
                }
            }
            return [
                'allowed_groups' => $typeFactory->multivalue(array_unique($groups)),
                '_extra_users' => $field['isHidden'] === 'c' ? $itemObject->getSpecialPermissionUsers($itemId, 'Modify') : [],
            ];
        }
        $ownerGroup = $itemObject->getOwnerGroup();
        return [
            'view_permission' => $typeFactory->identifier($itemObject->getViewPermission()),
            '_extra_users' => $itemObject->getSpecialPermissionUsers($itemId, 'View'),
            '_permission_accessor' => $itemObject->getPerms(),
            '_extra_groups' => $ownerGroup ? [$ownerGroup] : null,
        ];
    }

    /**
     * Folder (inbox, sent, trash, ...) of the Email Folder field value that holds the file
     */
    private function getEmailFolder($trackerId, $itemId, $fieldId, $fileId): string
    {
        $value = TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId, false);
        $folders = json_decode((string) $value, true);
        if (! is_array($folders)) {
            return in_array($fileId, explode(',', (string) $value)) ? 'inbox' : '';
        }
        foreach ($folders as $folder => $fileIds) {
            if (is_array($fileIds) && in_array($fileId, $fileIds)) {
                return (string) $folder;
            }
        }
        return '';
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'title_unstemmed',
            'language',
            'creation_date',
            'modification_date',
            'date',
            'contributors',
            'description',
            'filename',
            'filetype',
            'filesize',
            'hits',

            'gallery_id',
            'file_comment',
            'file_content',
            'ocr_content',

            'view_permission',
            'parent_object_id',
            'parent_object_type',

            'email_subject',
            'email_from',
            'email_sender',
            'email_recipient',
            'email_date',
            'email_flags',
            'email_content_type',
            'email_body',
            'email_plaintext',
            'email_html',
            'email_flags_text',
            'email_message_id',
            'email_tracker_id',
            'email_tracker_item_id',
            'email_tracker_field_id',
            'email_folder',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'title_unstemmed' => 'simpletext',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'date' => 'timestamp',
            'contributors' => 'multivalue',
            'description' => 'plaintext',
            'filename' => 'identifier',
            'filetype' => 'sortable',
            'filesize' => 'plaintext',
            'hits' => 'numeric',

            'gallery_id' => 'identifier',
            'file_comment' => 'plaintext',
            'file_content' => 'plaintext',
            'ocr_content' => 'plaintext',

            'view_permission' => 'identifier',
            'parent_object_id' => 'identifier',
            'parent_object_type' => 'identifier',

            'email_subject' => 'plaintext',
            'email_from' => 'plaintext',
            'email_sender' => 'plaintext',
            'email_recipient' => 'plaintext',
            'email_date' => 'timestamp',
            'email_flags' => 'multivalue',
            'email_content_type' => 'plaintext',
            'email_body' => 'plainmediumtext',
            'email_plaintext' => 'plainmediumtext',
            'email_html' => 'plainmediumtext',
            'email_flags_text' => 'plaintext',
            'email_message_id' => 'identifier',
            'email_tracker_id' => 'identifier',
            'email_tracker_item_id' => 'identifier',
            'email_tracker_field_id' => 'identifier',
            'email_folder' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        global $prefs;

        $fields = [
            'title' => true,
            'description' => true,
            'date' => true,
            'filename' => true,

            'file_comment' => false,
            'file_content' => false,
            'ocr_content' => false,
        ];

        if ($prefs['fgal_enable_email_indexing'] === 'y') {
            $fields['email_subject'] = true;
            $fields['email_body'] = true;
            $fields['email_plaintext'] = true;
            $fields['email_html'] = true;
        }

        return $fields;
    }
}
