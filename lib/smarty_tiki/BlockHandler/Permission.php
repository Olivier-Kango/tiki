<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

class Permission extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        if ($repeat) {
            return;
        }

        // Removing and Modifying a tracker item require a special permissions check
        if (! empty($params['type']) && $params['type'] == 'trackeritem') {
            $removePerms = ['remove_tracker_items','remove_tracker_items_pending','remove_tracker_items_closed'];
            $modifyPerms = ['modify_tracker_items','modify_tracker_items_pending','modify_tracker_items_closed'];

            $trklib = \TikiLib::lib('trk');
            $itemInfo = $trklib->get_tracker_item($params['object']);

            if (! $itemInfo) {
                return ""; //invalid tracker item.
            }

            $itemObject = \Tracker_Item::fromInfo($itemInfo);

            if (in_array($params['name'], $removePerms)) {
                if ($itemObject->canRemove()) {
                    return $content;
                }
            } elseif (in_array($params['name'], $modifyPerms)) {
                if ($itemObject->canModify()) {
                    return $content;
                }
            }
        }

        //Standard permissions check
        $context = [];

        if (isset($params['type'], $params['object'])) {
            $context['type'] = $params['type'];
            $context['object'] = $params['object'];
        }

        $perms = \Perms::get($context);
        $name = $params['name'];

        if ($perms->$name) {
            return $content;
        } else {
            return '';
        }
    }
}
