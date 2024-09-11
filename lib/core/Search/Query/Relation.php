<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tracker\Field\TrackerFieldRelation;

class Search_Query_Relation
{
    private $qualifier;
    private $type;
    private $object;

    public static function fromToken(Search_Expr_Token $token)
    {
        $token->setType('plaintext');
        $value = $token->getValue(new Search_Type_Factory_Direct());
        list($qualifier, $type, $object) = explode(':', $value->getValue(), 3);

        return new self($qualifier, $type, $object);
    }

    public static function token($qualifier, $type, $object)
    {
        $rel = new self($qualifier, $type, $object);
        return $rel->getToken();
    }

    public function __construct($qualifier, $type, $object)
    {
        $this->qualifier = $qualifier;
        $this->type = $type;
        $this->object = $object;
    }

    public function __toString()
    {
        return '"' . $this->getToken() . '"';
    }

    public function getToken()
    {
        return "{$this->qualifier}:{$this->type}:{$this->object}";
    }

    public function getQualifier()
    {
        return $this->qualifier;
    }

    public function getInvert()
    {
        $qualifier = TrackerFieldRelation::getInvertQualifier($this->qualifier);
        return new self($qualifier, $this->type, $this->object);
    }
}
