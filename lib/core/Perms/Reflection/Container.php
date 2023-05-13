<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
interface Perms_Reflection_Container
{
    public function add($group, $permission);
    public function remove($group, $permission);

    public function getDirectPermissions();
    public function getParentPermissions();
}
