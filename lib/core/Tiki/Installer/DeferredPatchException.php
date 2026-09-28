<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Installer;

use Exception;

/**
 * A schema patch cannot be applied yet, but later patches must still run.
 *
 * The patch is marked failed for this update and is not recorded in tiki_schema,
 * so the next database update tries it again. The exception message is shown to the user.
 */
class DeferredPatchException extends Exception
{
}
