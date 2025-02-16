<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\CustomRoute\Type;

use TikiLib;
use Tiki\CustomRoute\Type;

/**
 * Custom route based on a tracker field
 */
class TrackerField extends Type
{
    /**
     * @inheritdoc
     */
    public function getParams()
    {
        return [
            'tracker' => [
                'name' => tr('Tracker'),
                'type' => 'select',
                'required' => true,
                'function' => 'getTrackers',
            ],
            'tracker_field' => [
                'name' => tr('Field'),
                'type' => 'select',
                'required' => true,
                'function' => 'getTrackerFieldsWithValidation',
                'args' => ['tracker'],
            ],
            'wiki_page' => [
                'name' => tr('Wiki page'),
                'type' => 'select',
                'required' => false,
                'function' => 'getWikiPageNames'
            ],
        ];
    }

    /**
     * Get the list of trackers available to add a route
     *
     * @return array
     */
    public static function getTrackers()
    {
        $trklib = TikiLib::lib('trk');
        $trackers = $trklib->list_trackers(0, -1, 'name_asc', '');

        return ['' => ''] + $trackers['list'];
    }

    /**
     * Get the list of tracker items available for a given tracker
     *
     * @param $trackerId
     * @return array
     */
    public static function getTrackerFields($trackerId)
    {
        $trklib = TikiLib::lib('trk');
        $fields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');

        $list = ['' => ''];
        $list['itemId'] = tr('Tracker Item Id');
        foreach ($fields['data'] as $trkField) {
            $fieldId = $trkField['fieldId'];
            $fieldName = $trkField['name'];

            $list[$fieldId] = $fieldName;
        }


        return $list;
    }

    /**
     * Get the list of wiki pages available to add a route
     *
     * @return array
     */
    public static function getWikiPageNames()
    {
        $tikilib = TikiLib::lib('tiki');

        $list = ['' => ''];
        $pages = $tikilib->list_pages(0, -1, 'pageName_asc');
        foreach ($pages['data'] as $page) {
            $list[$page['page_id']] = $page['pageName'];
        }

        return $list;
    }

    /**
     * Get the list of tracker fields but only if the trackerId is valid
     *
     * @param $trackerId
     * @return array
     */
    public static function getTrackerFieldsWithValidation($trackerId)
    {
        // Validate if trackerId is present
        if (empty($trackerId)) {
            return ['' => tr('No tracker selected')];
        }

        return self::getTrackerFields($trackerId);
    }
}
