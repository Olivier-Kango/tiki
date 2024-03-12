<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_OfflineController
{
    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_cache($input)
    {
        $result = [];
        $trackers = TikiLib::lib('trk')->get_trackers_options(null, 'allowOffline', 'y');
        foreach ($trackers as $row) {
            $itemObject = Tracker_Item::newItem($row['trackerId']);
            if (! $itemObject->canModify()) {
                continue;
            }
            $definition = Tracker_Definition::get($row['trackerId']);
            $info = $definition->getInformation();
            // TODO: prepare data for Vue.js components
            $info['fields'] = $itemObject->prepareInput($input);
            $result[] = $info;
        }
        return $result;
    }
}
