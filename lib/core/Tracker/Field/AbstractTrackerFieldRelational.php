<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

use Tracker\RelationInfoRelation;

/**
 * Represents both Relations and ItemLink
 */
abstract class AbstractTrackerFieldRelational extends AbstractTrackerField
{
    /**
     * A string, garanted to be the same for both "sides" of the relation, and otherwise unique in the system
     *
     * @return string
     */
    abstract public function getRelationId(): string;

    /**
     * Internal method, used to find and make available relations not defined
     * on this tracker
     *
     * @return Id of the tracker where the relation is defined, possibly the one this is called on for self joins
    */
    abstract public function getDistantTrackerId(): ?int;

    /**
     *
     * @return RelationInfoRelation|null Return is null if the relation exists but isn't yet valid (one example is if Relation field hasn't had the distant tracker set yet)
     */
    abstract public function getRelationInfo(): ?RelationInfoRelation;
}
