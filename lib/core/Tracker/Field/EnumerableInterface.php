<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

interface EnumerableInterface extends ItemFieldInterface
{
    /**
     * For trackerfields having finite enumerable values (Dropdown, Users, etc.)
     * return the values the user can set a tracker item to.  Typically used to
     * show dropdowns, configure query filters, etc.
     *
     * @return ['value' => 'labelForValue']
     */
    public function getPossibleItemValues();

    /**
     * Is the user currently allowed to select more than one value in a single field?  If so, the value could be a coma separated list of keys.
     *
     * This is necessary since there isn't a uniform config key for this that is
     * usable for all field types.
     *
     * @return boolean
     */
    public function canHaveMultipleValues();
}
