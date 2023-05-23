<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

use TikiLib;
use JitFilter;

/**
 * SystemTrackerCreator
 *
 * Automatically creates the Relation metadata system tracker and assigns
 * field configuration values.
 */
class SystemTrackerCreator
{
    protected $trackerId;
    protected $fieldIds;

    public function __construct()
    {
        $this->trklib = TikiLib::lib('trk');
        $this->tikilib = TikiLib::lib('tiki');
    }

    public function initFromAdmin()
    {
        $this->trackerId = $this->createTracker();
        $this->fieldIds = $this->createFields();
        $this->assignFieldConfiguration();
    }

    protected function createTracker()
    {
        $name = tr('Relation Metadata');
        $description = tr('System tracker used to describe relations and their particular metadata like description, label, direction, type (like many-to-one).');
        $descriptionIsParsed = 'n';
        $input = new JitFilter([
            'fieldPrefix' => 'relationMetadata',
            'permName' => 'relationMetadata',
        ]);
        $data = $this->trklib->trackerOptionsFromInput($input);
        return $this->trklib->replace_tracker(0, $name, $description, $data, $descriptionIsParsed);
    }

    protected function createFields()
    {
        $fieldIds = [];
        $options = [
            Semantics::GENERIC => tr('relates to'),
            Semantics::BLOCKS => tr('blocks'),
            Semantics::IS_BLOCKED_BY => tr('is blocked by'),
            Semantics::DUPLICATES => tr('duplicates'),
            Semantics::IS_DUPLICATED_BY => tr('is duplicated by'),
            Semantics::CHILD_OF => tr('is a child of'),
            Semantics::PARENT_OF => tr('is a parent of'),
            Semantics::FIXES => tr('fixes'),
            Semantics::IS_FIXED_BY => tr('is fixed by'),
        ];
        $opts = [];
        foreach ($options as $key => $val) {
            $opts[] = strval($key) . '=' . $val;
        }
        $options = $opts;
        $fieldIds['description'] = $this->trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'Description',
            'D',
            'y',
            'y',
            'y',
            'y',
            'n',
            'y',
            10,
            json_encode(['options' => $options]),
            tr('Describe the relation - what is the semantics of relating the items?'),
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'relationMetadataDescription'
        );
        $fieldIds['label'] = $this->trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'Label',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'n',
            20,
            '',
            '',
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'relationMetadataLabel'
        );
        $fieldIds['direction'] = $this->trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'Directional?',
            'c',
            'n',
            'y',
            'y',
            'y',
            'n',
            'n',
            30,
            '',
            tr('Is the relation directional or not?'),
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'relationMetadataDirection'
        );
        $options = [
            'one-to-one' => tr('one-to-one'),
            'many-to-one' => tr('many-to-one'),
            'one-to-many' => tr('one-to-many'),
            'many-to-many' => tr('many-to-many'),
        ];
        $fieldIds['type'] = $this->trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'Type',
            'd',
            'n',
            'y',
            'y',
            'y',
            'n',
            'n',
            40,
            json_encode(['options' => $options]),
            tr('What type is the relation?'),
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'relationMetadataType'
        );
        return $fieldIds;
    }

    protected function assignFieldConfiguration()
    {
        $this->tikilib->set_preference('tracker_system_relations_tracker', $this->trackerId);
        $this->tikilib->set_preference('tracker_system_relations_description', $this->fieldIds['description']);
        $this->tikilib->set_preference('tracker_system_relations_label', $this->fieldIds['label']);
        $this->tikilib->set_preference('tracker_system_relations_direction', $this->fieldIds['direction']);
        $this->tikilib->set_preference('tracker_system_relations_type', $this->fieldIds['type']);
    }
}
