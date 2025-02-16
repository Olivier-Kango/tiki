<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function payment_behavior_cancel_cart_order($items = [])
{
    global $tikilib;
    if (! count($items)) {
        return false;
    }

    $mid = " WHERE `itemId` IN (" . implode(",", array_fill(0, count($items), '?')) . ")";
    $query = "UPDATE `tiki_tracker_items` SET `status` = 'c'" . $mid;
    $tikilib->query($query, $items);
    return true;
}
