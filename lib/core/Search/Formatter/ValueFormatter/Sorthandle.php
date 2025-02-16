<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ValueFormatter_Sorthandle extends Search_Formatter_ValueFormatter_Abstract
{
    public function render($name, $value, array $entry)
    {
        if (substr($name, 0, 14) !== 'tracker_field_') {
            return "";
        }

        $tracker = Tracker_Definition::get($entry['tracker_id']);
        if (! is_object($tracker)) {
            return $value;
        }
        $field = $tracker->getField(substr($name, 14));
        $field['value'] = $value;

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
                'list_mode' => 'n',
                'editable' => 'direct',
            ]
        );
        return '~np~<span class="fas fa-sort inline-sort-handle" data-current-value="' . smarty_modifier_escape($value) . '"></span><span class="hidden">' . $rendered . '</span>~/np~';
    }

    public function canCache()
    {
        return false;
    }
}
