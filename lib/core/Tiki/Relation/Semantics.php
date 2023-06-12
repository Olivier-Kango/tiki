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
    protected string $relation = '';

    public function __construct(string $behaviour, string $relation)
    {
        if (! isset(self::BEHAVIOUR_LIST[$behaviour])) {
            throw new \Exception(tr('Incorrect relationship behaviour requested:') . ' ' . $behaviour);
        }
        $this->behaviour = $behaviour;
        $this->relation = $relation;
        return $this;
    }

    public function isMultiple()
    {
        switch (self::BEHAVIOUR_LIST[$this->behaviour]['cardinality']) {
            case self::MANY_TO_MANY:
                return true;
            case self::ONE_TO_MANY:
                if ($this->invert()) {
                    return true;
                } else {
                    return false;
                }
        }
        throw new \Exception(tr('Unknown relation cardinality specified for behaviour %0', $this->behaviour));
    }

    public function isDirectional()
    {
        return self::BEHAVIOUR_LIST[$this->behaviour]['directional'];
    }

    public function invert()
    {
        return substr($this->relation, -7) === '.invert';
    }
}
