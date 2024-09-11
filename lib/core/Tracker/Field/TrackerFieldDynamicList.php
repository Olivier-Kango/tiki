<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

use Tiki\Relation\Semantics;
use Tracker\RelationInfoEntity;
use Tracker_Definition;
use Tracker\RelationInfoRelation;

class TrackerFieldDynamicList extends AbstractTrackerFieldRelational
{
    public function getRelationId(): string
    {
        return 'DL_t' . $this->trackerDefinition->getId() . '_' . 'f' . $this->getId();
    }
    public function getDistantTrackerId(): ?int
    {
        return $this->getOption('trackerId');
    }

    public function getRelationInfo(): ?RelationInfoRelation
    {
        $relationInfo = new RelationInfoRelation();
        $distantTrackerId = $this->getDistantTrackerId();
        if ($distantTrackerId) {
            $distantTracker = Tracker_Definition::get($distantTrackerId);
        } else {
            //While editing the field, the distant tracker may not yet have been set
            return null;
        }

        $relationInfo->id = $this->getRelationId();
        $relationInfo->name = $this->getName();

        $first = new RelationInfoEntity();
        $first->instance = $distantTracker;
        $this->isMandatory() ? $first->cardinalityMin = 1 :
        $first->cardinalityMin = 0;
        $first->cardinalityMax = PHP_INT_MAX;
        $relationInfo->first = $first;

        $second = new RelationInfoEntity();
        $second->instance = $this->trackerDefinition;
        $this->isMandatory() ? $second->cardinalityMin = 1 :
        $second->cardinalityMin = 0;
        $second->cardinalityMax = 1;
        $relationInfo->second = $second;
        return $relationInfo;
    }
}
