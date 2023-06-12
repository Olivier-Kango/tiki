<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

class Semantics
{
    public const MANY_TO_MANY = 1;
    public const ONE_TO_MANY = 2;
    public const BEHAVIOUR_LIST = [
        'GENERIC_DIRECTIONAL' => ['cardinality' => self::MANY_TO_MANY, 'directional' => true],
        'GENERIC_NON_DIRECTIONAL' => ['cardinality' => self::MANY_TO_MANY, 'directional' => false],
        'GENERIC_ONE_TO_MANY' => ['cardinality' => self::ONE_TO_MANY, 'directional' => true],
    ];

    protected string $behaviour = '';

    public function __construct($behaviour)
    {
        if (! isset(self::BEHAVIOUR_LIST[$behaviour])) {
            throw new \Exception(tr('Incorrect relationship behaviour requested:') . ' ' . $behaviour);
        }
        $this->behaviour = $behaviour;
        return $this;
    }

    public function isMultiple()
    {
        return self::BEHAVIOUR_LIST[$this->behaviour]['cardinality'] == self::MANY_TO_MANY;
    }

    public function isDirectional()
    {
        return self::BEHAVIOUR_LIST[$this->behaviour]['directional'];
    }
}
