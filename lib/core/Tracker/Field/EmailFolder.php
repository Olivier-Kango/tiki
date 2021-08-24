<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_EmailFolder extends Tracker_Field_Files implements Tracker_Field_Exportable
{
    public static function getTypes()
    {
        global $prefs;

        $options = [
            'EF' => [
                'name' => tr('Email Folder'),
                'description' => tr('Associate email messages with tracker items.'),
                'prefs' => ['trackerfield_email_folder', 'feature_file_galleries'],
                'tags' => ['advanced'],
                'help' => 'Email Folder Tracker Field',
                'default' => 'y',
                'params' => [
                    'galleryId' => [
                        'name' => tr('Gallery ID'),
                        'description' => tr('File gallery to upload new emails into.'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'file_gallery',
                    ],
                    'useFolders' => [
                        'name' => tr('Use Folders'),
                        'description' => tr('Use separate folders like Inbox, Sent, Trash.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'inboxName' => [
                        'name' => tr('Inbox Name'),
                        'description' => tr('Name of the Inbox folder.'),
                        'filter' => 'text',
                        'default' => 'Inbox',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'sentName' => [
                        'name' => tr('Sent Name'),
                        'description' => tr('Name of the Sent folder.'),
                        'filter' => 'text',
                        'default' => 'Sent',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'trashName' => [
                        'name' => tr('Trash Name'),
                        'description' => tr('Name of the Trash folder.'),
                        'filter' => 'text',
                        'default' => 'Trash',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                ],
            ],
        ];
        return $options;
    }

    function getFieldData(array $requestData = [])
    {
        global $prefs;
        $filegallib = TikiLib::lib('filegal');

        $galleryId = (int) $this->getOption('galleryId');
        $galinfo = $filegallib->get_file_gallery($galleryId, false);
        if (! $galinfo || empty($galinfo['galleryId'])) {
            Feedback::error(tr('%0 field: Gallery #%1 not found', $this->getConfiguration('name'), $galleryId));
            return [];
        }

        $value = $this->getValue();
        $decoded = json_decode($value, true);
        if ($decoded !== null) {
            $fileIds = $decoded;
        } else {
            $fileIds = [
                'inbox' => array_filter(explode(',', $value))
            ];
        }

        // Obtain the information from the database for display
        $emails = [];
        foreach ($fileIds as $folder => $files) {
            $emails[$folder] = [];
            foreach ($files as $fileId) {
                if (empty($fileId)) {
                    continue;
                }
                $file_object = Tiki\FileGallery\File::id($fileId);
                if (! $file_object->exists()) {
                    continue;
                }
                $parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
                $parsed_fields['fileId'] = $fileId;
                $parsed_fields['trackerId'] = $this->getTrackerDefinition()->getConfiguration('trackerId');
                $parsed_fields['itemId'] = $this->getItemId();
                $parsed_fields['fieldId'] = $this->getConfiguration('fieldId');
                $emails[$folder][] = $parsed_fields;
            }
        }

        return [
            'galleryId' => $galleryId,
            'emails' => $emails,
            'count' => count($fileIds, COUNT_RECURSIVE),
            'value' => $value,
        ];
    }

    function renderInput($context = [])
    {
        return tr("Emails can be copied or moved here via the Webmail interface.");
    }

    function renderOutput($context = [])
    {
        if (! isset($context['list_mode'])) {
            $context['list_mode'] = 'n';
        }

        $value = $this->getValue();

        if ($context['list_mode'] === 'csv') {
            return $value;
        }

        $emails = $this->getConfiguration('emails');

        if ($context['list_mode'] === 'text') {
            $folderFormatter = function ($emails) {
                return implode(
                    "\n",
                    array_map(
                        function ($email) {
                            return $email['subject'];
                        },
                        $emails
                    )
                );
            };
            if ($this->getOption('useFolders')) {
                $result = "";
                foreach (['inbox', 'sent', 'trash'] as $folder) {
                    if (! empty($emails[$folder])) {
                        $result .= $this->getOption($folder . 'Name') . "\n";
                        $result .= $folderFormatter($emails[$folder]);
                    }
                }
            } else {
                return $folderFormatter($emails['inbox']);
            }
        }

        return $this->renderTemplate('trackeroutput/email_folder.tpl', $context, [
            'emails' => $emails,
            'count' => $this->getConfiguration('count'),
        ]);
    }

    function handleSave($value, $oldValue)
    {
        $existing = json_decode($oldValue, true);
        if ($existing === null) {
            $existing = [
                'inbox' => array_filter(explode(',', $oldValue))
            ];
        }
        if (isset($value['new'])) {
            $folder = $value['folder'] ?? 'inbox';
            if ($this->getOption('useFolders') || $folder == 'inbox') {
                $this->addEmail($existing[$folder], $value['new']);
            }
        } elseif (isset($value['delete'])) {
            $this->deleteEmail($existing, $value['delete']);
        }
        return parent::handleSave(json_encode($existing), $oldValue);
    }

    function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();
        $emails = $this->getConfiguration('emails');
        if (! is_array($emails)) {
            $data = $this->getFieldData();
            $emails = $data['emails'];
        }

        $subjects = [];
        $dates = [];
        $senders = [];
        $recipients = [];
        foreach ($emails as $folder => $folder_emails) {
            foreach ($folder_emails as $email) {
                $subjects[] = $email['subject'];
                $dates[] = $email['date'];
                $senders[] = $email['sender'];
                $recipients[] = $email['recipient'];
            }
        }

        $out = [
            $baseKey => $typeFactory->identifier($value),
            "{$baseKey}_subjects" => $typeFactory->multivalue($subjects),
            "{$baseKey}_dates" => $typeFactory->multivalue($dates),
            "{$baseKey}_senders" => $typeFactory->multivalue($senders),
            "{$baseKey}_recipients" => $typeFactory->multivalue($recipients),
        ];
        return $out;
    }

    function getProvidedFields()
    {
        $baseKey = $this->getBaseKey();
        $fields = [
            $baseKey,
            "{$baseKey}_subjects",
            "{$baseKey}_dates",
            "{$baseKey}_senders",
            "{$baseKey}_recipients",
        ];
        return $fields;
    }

    function getGlobalFields()
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey => true];
    }

    function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }

    protected function addEmail(&$existing, $file)
    {
        $filegallib = TikiLib::lib('filegal');
        $galleryId = (int) $this->getOption('galleryId');
        $galinfo = $filegallib->get_file_gallery($galleryId, false);
        if (! $galinfo || empty($galinfo['galleryId'])) {
            Feedback::error(tr('%0 field: Gallery #%1 not found', $this->getConfiguration('name'), $galleryId));
            return;
        }
        $fileId = $filegallib->upload_single_file($galinfo, $file['name'], $file['size'], $file['type'], $file['content']);
        if ($fileId) {
            $existing[] = $fileId;
        }
    }

    protected function deleteEmail(&$existing, $fileId)
    {
        foreach ($existing as $folder => $_) {
            if (($key = array_search($fileId, $existing[$folder])) !== false) {
                unset($existing[$folder][$key]);
                $existing[$folder] = array_values($existing[$folder]);
                if ($this->getOption('useFolders') && $folder != 'trash') {
                    $existing['trash'][] = $fileId;
                } else {
                    $filegallib = TikiLib::lib('filegal');
                    $info = $filegallib->get_file_info($fileId);
                    if ($info) {
                        $filegallib->remove_file($info);
                    }
                }
                break;
            }
        }
    }
}
