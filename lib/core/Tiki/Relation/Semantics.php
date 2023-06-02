<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

class Semantics
{
    const MANY_TO_MANY = 1;
    const ONE_TO_MANY = 2;
    const BEHAVIOUR_LIST = [
        'GENERIC_DIRECTINAL' => ['cardiality' => self::MANY_TO_MANY, 'directional' => true],
        'GENERIC_NON_DIRECTIONAL' => ['cardiality' => self::MANY_TO_MANY, 'directional' => false],
        'GENERIC_ONE_TO_MANY' => ['cardiality' => self::ONE_TO_MANY, 'directional' => true],
    ];
}
