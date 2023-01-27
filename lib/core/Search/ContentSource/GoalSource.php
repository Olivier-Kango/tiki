<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Search\ContentSource;

use Search_ContentSource_Interface;
use Search_Type_Factory_Interface;
use TikiDb;

class GoalSource implements Search_ContentSource_Interface
{
    private $table;

    public function __construct()
    {
        $this->table = TikiDb::get()->table('tiki_goals');
    }

    public function getDocuments()
    {
        return $this->table->fetchColumn('goalId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
    {
        global $prefs;

        $goal = $this->table->fetchRow(['name', 'type', 'description', 'enabled', 'daySpan', 'from', 'to', 'eligible', 'conditions', 'rewards'], [
            'goalId' => $objectId,
        ]);

        if ($goal) {
            return [
                'title' => $typeFactory->sortable($goal['name']),
                'description' => $typeFactory->plaintext($goal['description']),
                'goal_type' => $typeFactory->identifier($goal['type']),
                'enabled' => $typeFactory->numeric($goal['enabled']),
                'day_span' => $typeFactory->numeric($goal['daySpan']),
                'from' => $typeFactory->timestamp($goal['from'] ? strtotime($goal['from'] . ' UTC') : null),
                'to' => $typeFactory->timestamp($goal['to'] ? strtotime($goal['to'] . ' UTC') : null),
				'eligible' => $typeFactory->json($goal['eligible']),
                'conditions' => $typeFactory->json($goal['conditions']),
                'rewards' => $typeFactory->json($goal['rewards']),
				'allowed_groups' => $typeFactory->multivalue(['Anonymous', 'Registered']),
            ];
        } else {
            return false;
        }
    }

    public function getProvidedFields()
    {
        return ['title', 'description', 'goal_type', 'enabled', 'day_span', 'from', 'to', 'eligible', 'conditions', 'rewards'];
    }

    public function getProvidedFieldTypes()
    {
        return [
            'title' => 'sortable',
            'description' => 'plaintext',
            'goal_type' => 'identifier',
            'enabled' => 'numeric',
            'day_span' => 'numeric',
            'from' => 'timestamp',
            'to' => 'timestamp',
            'eligible' => 'json',
            'conditions' => 'json',
            'rewards' => 'json',
        ];
    }

    public function getGlobalFields()
    {
        return [
            'title' => true,
            'description' => true,
        ];
    }
}
