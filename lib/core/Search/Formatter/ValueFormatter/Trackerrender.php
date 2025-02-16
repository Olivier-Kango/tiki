<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ValueFormatter_Trackerrender extends Search_Formatter_ValueFormatter_Abstract
{
    private $list_mode = 'n';
    private $cancache = null;
    private $editable = false;
    private $group = false;

    public function __construct($arguments)
    {
        if (isset($arguments['list_mode']) && $arguments['list_mode'] !== 'n') {
            if ($arguments['list_mode'] == 'csv') {
                $this->list_mode = 'csv';
            } else {
                $this->list_mode = 'y';
            }
        }

        if (isset($arguments['editable'])) {
            $parts = explode(' ', $arguments['editable']);
            $editable = array_shift($parts);
            $group = array_shift($parts);

            if (in_array($editable, ['block', 'inline', 'dialog'])) {
                $this->editable = $editable;
                $this->group = $group;
            }
        }
    }

    public function render($name, $value, array $entry)
    {
        if ($name === 'tracker_status') {
            switch ($value) {
                case 'o':
                    $status = 'open';
                    $istatus = tra('Open');
                    break;
                case 'p':
                    $status = 'pending';
                    $istatus = tra('Pending');
                    break;
                default:
                case 'c':
                    $status = 'closed';
                    $istatus = tra('Closed');
                    break;
            }

            $smarty = TikiLib::lib('smarty');
            return smarty_function_icon(['name' => 'status-' . $status, 'iclass' => 'tips', 'ititle' => ':'
                . $istatus ], $smarty->getEmptyInternalTemplate());
        } elseif (substr($name, 0, 14) !== 'tracker_field_' && $name !== 'title') {
            return $value;
        }

        $tracker = Tracker_Definition::get($entry['tracker_id']);
        if (! is_object($tracker)) {
            return $value;
        }
        if ($name === 'title') {
            // function getField works with either id of permName
            $nameOrId = $tracker->getMainFieldId($entry['tracker_id']);
        } else {
            $nameOrId = substr($name, 14);
        }
        $field = $tracker->getField($nameOrId);

        if ($name === 'title') {
            $name = 'tracker_field_' . $field['permName'];
        }

        if (! $field) {
            if (Perms::get()->tracker_admin) {
                return '~np~' . tr('Field rendering requested but field not found: %0', $name) . '~/np~';
            } else {
                return '';
            }
        }

        // check translations of multilingual fields
        global $prefs;
        if ($field['isMultilingual'] === 'y' && isset($entry[$name . '_' . $prefs['language']])) {
            $name = $name . '_' . $prefs['language'];
            $value = $entry[$name];
        }
        // TextArea fields need the raw wiki syntax here for it to get wiki parsed if necessary
        if ($field['type'] === 'a' && isset($entry[$name . '_raw'])) {
            $value = $entry[$name . '_raw'];
        } elseif (in_array($field['type'], ['f', 'j'])) {
            $formatter = new Search_Formatter_ValueFormatter_Datetime();
            $value = $formatter->timestamp($value);
        }
        $field['value'] = $value;

        $this->cancache = ! in_array($field['type'] ?? '', ['STARS', 's']); // don't cache ratings fields

        if ($this->editable) {
            // Caching breaks inline editing
            $this->cancache = false;
        }

        $item = [];
        if ($entry['object_type'] == 'trackeritem') {
            $item['itemId'] = $entry['object_id'];
        }

        $trklib = TikiLib::lib('trk');
        $rendered = $trklib->field_render_value(
            [
                'item' => $item,
                'field' => $field,
                'process' => 'y',
                'search_render' => 'y',
                'list_mode' => $this->list_mode,
                'editable' => $this->editable,
                'editgroup' => $this->group,
                'showpopup' => $field['isMain'] ?? false,
            ]
        );
        return '~np~' . $rendered . '~/np~';
    }

    public function canCache()
    {
        if ($this->cancache === null) {
            trigger_error('Search_Formatter_ValueFormatter_Trackerrender->canCache() called before field rendered, assuming "true"');
            $this->cancache = true;
        }
        return $this->cancache;
    }
}
