<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

class Semantics
{
    const GENERIC = 1;
    const BLOCKS = 2;
    const IS_BLOCKED_BY = 3;
    const DUPLICATES = 4;
    const IS_DUPLICATED_BY = 5;
    const CHILD_OF = 6;
    const PARENT_OF = 7;
    const FIXES = 8;
    const IS_FIXED_BY = 9;
}
