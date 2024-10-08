<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Composer\Composer;
use Composer\Script\Event;
use Exception;

class ValidateVersion
{
    public static function validate(Event $event)
    {
        try {
            $version = Composer::getVersion();
            if (version_compare($version, '2.0.0', '<')) {
                throw new Exception("Composer 2.0.0 or higher is required. Composer version used: $version");
            }
        } catch (Exception $e) {
            $event->getIO()->writeError("<error>{$e->getMessage()}</error>");
            exit(1);
        }
    }
}
