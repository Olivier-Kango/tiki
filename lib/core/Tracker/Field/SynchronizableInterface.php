<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

interface SynchronizableInterface extends FieldInterface
{
    public function importRemote($value);

    public function exportRemote($value);

    public function importRemoteField(array $info, array $syncInfo);
}
