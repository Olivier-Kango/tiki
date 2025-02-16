<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_UserSource implements Search_ContentSource_Interface
{
    private $db;
    private $user;
    private $tiki;
    private $geo;
    private $trk;
    private $visibility;

    public function __construct($visibility)
    {
        $this->db = TikiDb::get();
        $this->user = TikiLib::lib('user');
        $this->tiki = TikiLib::lib('tiki');
        $this->geo = TikiLib::lib('geo');
        $this->trk = TikiLib::lib('trk');
        $this->visibility = $visibility;
    }

    public function getDocuments()
    {
        return $this->db->table('users_users')->fetchColumn('login', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $detail = $this->user->get_user_details($objectId, false);

        if (empty($detail['info'])) {
            return false;
        }

        $name = $objectId;
        if (! empty($detail['preferences']['realName'])) {
            $name = $detail['preferences']['realName'];
        }

        $content = '';
        if ($prefs['feature_wiki_userpage'] == 'y' && ! empty($prefs['feature_wiki_userpage_prefix'])) {
            $page = $prefs['feature_wiki_userpage_prefix'] . $objectId;
            if ($info = $this->tiki->get_page_info($page, true, true)) {
                $content = $info['data'];
            }
        }

        $loc = $this->geo->build_location_string($detail['preferences']);

        $country = '';
        if (isset($detail['preferences']['country'])) {
            $country = $detail['preferences']['country'];
        }
        $gender = '';
        if (isset($detail['preferences']['gender'])) {
            $gender = $detail['preferences']['gender'];
        }
        $homePage = '';
        if (isset($detail['preferences']['homePage'])) {
            $homePage = $detail['preferences']['homePage'];
        }
        $realName = '';
        if (isset($detail['preferences']['realName'])) {
            $realName = $detail['preferences']['realName'];
        }
        if ($prefs['allowmsg_is_optional'] == 'y' && isset($detail['preferences']['allowMsgs'])) {
            $allowMsgs = $detail['preferences']['allowMsgs'];
        } else {
            $allowMsgs = 'y';
        }
        if (isset($detail['preferences']['user_style'])) {
            $user_style = $detail['preferences']['user_style'];
        } else {
            $user_style = isset($prefs['site_style']) ? $prefs['site_style'] : "" ;
        }

        $user_language = $this->tiki->get_language($objectId);
        $langLib = TikiLib::lib('language');
        $user_language_text = $langLib->format_language_list([$user_language]);

        $userPage = $prefs['feature_wiki_userpage_prefix'] . $objectId;
        if (! $this->tiki->page_exists($userPage)) {
            $userPage = "";
        }


        $data = [
            'title' => $typeFactory->sortable($name),
            'title_unstemmed' => $typeFactory->simpletext($name), // for wildcard searches as wildcard doesn't play well with stemming (e.g. *leslie* doesn't match leslie)
            'creation_date' => $typeFactory->timestamp($detail['info']['created']), // same as registration date
            'date' => $typeFactory->timestamp($detail['info']['created']),
            'lastlogin_date' => $typeFactory->timestamp($detail['info']['lastLogin']),
            'currentlogin_date' => $typeFactory->timestamp($detail['info']['currentLogin']),

            'wiki_content' => $typeFactory->wikitext($content),

            'user_country' => $typeFactory->sortable($country),
            'user_gender' => $typeFactory->sortable($gender),
            'user_homepage' => $typeFactory->sortable($homePage),
            'user_realName' => $typeFactory->sortable($realName),
            'user_allowmsgs' => $typeFactory->sortable($allowMsgs),
            'user_language' => $typeFactory->multivalue($user_language),
            'user_style' => $typeFactory->sortable($user_style),
            'user_page' => $typeFactory->sortable($userPage),

            'geo_located' => $typeFactory->identifier(empty($loc) ? 'n' : 'y'),
            'geo_location' => $typeFactory->identifier($loc),

            'searchable' => $typeFactory->identifier($this->userIsIndexed($detail) ? 'y' : 'n'),
            'groups' => $typeFactory->multivalue($detail['groups']),
            '_extra_groups' => ['Registered'], // Add all registered to allowed groups

            'view_permission' => $typeFactory->identifier('tiki_p_list_users'),
        ];

        $data = array_merge($data, $this->getTrackerFieldsForUser($objectId, $typeFactory));

        return $data;
    }

    private function userIsIndexed($detail)
    {
        if ($this->visibility == 'all') {
            return true;
        } elseif (isset($detail['preferences']['user_information'])) {
            return $detail['preferences']['user_information'] == 'public';
        } else {
            return false;
        }
    }

    public function getProvidedFields(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [
            'title',
            'title_unstemmed',
            'creation_date',
            'date',
            'wiki_content',

            'user_country',
            'user_gender',
            'user_homepage',
            'user_realName',
            'user_allowmsgs',
            'user_language',
            'user_style',
            'user_page',

            'geo_located',
            'geo_location',

            'searchable',
            'groups',
            '_extra_groups',
        ];

        foreach ($this->getAllIndexableHandlers() as $baseKey => $handler) {
            $data = array_merge($data, $handler->getProvidedFields($baseKey));
        }

        return array_unique($data);
    }

    public function getProvidedFieldTypes(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [
            'title' => 'sortable',
            'title_unstemmed' => 'simpletext',
            'creation_date' => 'timestamp',
            'date' => 'timestamp',
            'wiki_content' => 'wikitext',

            'user_country' => 'sortable',
            'user_gender' => 'sortable',
            'user_homepage' => 'sortable',
            'user_realName' => 'sortable',
            'user_allowmsgs' => 'sortable',
            'user_language' => 'multivalue',
            'user_style' => 'sortable',
            'user_page' => 'sortable',

            'geo_located' => 'identifier',
            'geo_location' => 'identifier',

            'searchable' => 'identifier',
            'groups' => 'multivalue',
        ];

        foreach ($this->getAllIndexableHandlers() as $baseKey => $handler) {
            $data = array_merge($data, $handler->getProvidedFieldTypes($baseKey));
        }

        return $data;
    }

    public function getGlobalFields(): array
    {
        static $data;

        if (is_array($data)) {
            return $data;
        }

        $data = [
            'title' => true,
            'date' => true,

            'wiki_content' => false,
            'user_country' => true,
        ];

        foreach ($this->getAllIndexableHandlers() as $baseKey => $handler) {
            $data = array_merge($data, $handler->getGlobalFields($baseKey));
        }

        return $data;
    }

    private function getAllIndexableHandlers()
    {
        //We have horrible data validation so usersTrackerId could be both null or 0 - benoitg - 2024-03-11
        $result = $this->db->fetchAll("SELECT DISTINCT usersTrackerId FROM users_groups WHERE usersTrackerId IS NOT NULL AND usersTrackerId != 0");
        $handlers = [];
        foreach ($result as $row) {
            if ($definition = Tracker_Definition::get($row['usersTrackerId'])) {
                $handlers = array_merge($handlers, Search_ContentSource_TrackerItemSource::getIndexableHandlers($definition));
            }
        }

        return $handlers;
    }

    private function getTrackerFieldsForUser($user, $typeFactory)
    {
        $result = $this->db->fetchAll(
            "SELECT usersTrackerId trackerId, itemId
            FROM
                users_groups
                INNER JOIN tiki_tracker_item_fields ON usersFieldId = fieldId
            WHERE value = ? AND usersTrackerId IS NOT NULL AND usersTrackerId != 0
            ",
            [$user]
        );

        $data = [];
        foreach ($result as $row) {
            $definition = Tracker_Definition::get($row['trackerId']);

            if (! $definition) {
                continue;
            }

            $item = $this->trk->get_tracker_item($row['itemId']);
            if (! $item) {
                continue;
            }

            $data = array_merge($data, [
                'tracker_item_id' => $typeFactory->identifier($row['itemId']),
                'tracker_item_status' => $typeFactory->identifier($item['status'] ?? false),
            ]);

            foreach (Search_ContentSource_TrackerItemSource::getIndexableHandlers($definition, $item) as $baseKey => $handler) {
                $data = array_merge($data, $handler->getDocumentPart($typeFactory));
            }
        }

        return $data;
    }
}
