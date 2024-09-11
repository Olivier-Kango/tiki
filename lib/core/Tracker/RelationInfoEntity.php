<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tracker;

class RelationInfoEntity
{
    public object $instance;  //Typically an AbstractTrackerItem, but will be expanded in the future
    public int $cardinalityMin;
    public int $cardinalityMax;
}
