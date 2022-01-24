<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class Search_Manticore_Decorator
{
    protected $search;
    protected $index;

    public function __construct(\Manticoresearch\Search $search, Search_Manticore_Index $index)
    {
        $this->search = $search;
        $this->index = $index;
    }

    protected function getNodeField($node)
    {
        $field = $node->getField();
        $this->ensureHasField($field);
        return $field;
    }

    protected function ensureHasField($field)
    {
        global $prefs;

        $fields = preg_split('/\s*,\s*/', $field);
        foreach ($fields as $field) {
            $mapping = $this->index ? $this->index->getFieldMapping($field) : new stdClass();
            if ((empty($mapping) || empty((array)$mapping)) && $prefs['search_error_missing_field'] === 'y') {
                if (preg_match('/^tracker_field_/', $field)) {
                    $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', $field);
                    if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                        $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                    }
                } else {
                    $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', $field, $field);
                }
                $e = new Search_Manticore_Exception($msg);
                if ($field == 'tracker_id') {
                    $e->suppress_feedback = true;
                }
                throw $e;
            }
        }
    }
}
