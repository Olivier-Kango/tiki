<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Repairs the values stored by Computed (type C) tracker fields, which displayed as empty
 * because computeFields() saved to the "itemId" event argument while tracker item events
 * carry it as "object", sending every result to itemId 0.
 *
 * That is fixed in Tracker_Field_Computed. This recomputes and stores the computed fields
 * of every item of the trackers holding one.
 *
 * @param Installer $installer
 * @return void
 */
function upgrade_20260924_fix_computed_tracker_field_values_tiki(Installer $installer)
{
    $trackers = $installer->fetchAll(
        'SELECT DISTINCT `trackerId` FROM `tiki_tracker_fields` WHERE `type` = ?',
        ['C']
    );

    foreach (array_column($trackers, 'trackerId') as $trackerId) {
        $items = $installer->fetchAll(
            'SELECT `itemId` FROM `tiki_tracker_items` WHERE `trackerId` = ?',
            [(int) $trackerId]
        );

        foreach (array_column($items, 'itemId') as $itemId) {
            Tracker_Field_Computed::recomputeItem($trackerId, $itemId);
        }
    }
}
