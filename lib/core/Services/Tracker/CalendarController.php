<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_CalendarController
{
    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_list($input)
    {
        global $prefs, $user, $tikilib;
        static $id = 0;
        ++$id;

        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $index = $unifiedsearchlib->getIndex();

        $start = 'tracker_field_' . $input->beginField->word();
        $end = 'tracker_field_' . $input->endField->word();

        if ($resource = $input->resourceField->word()) {
            $resource = 'tracker_field_' . $resource;
        }

        if ($coloring = $input->coloringField->word()) {
            $coloring = 'tracker_field_' . $coloring;
        }

        $query = $unifiedsearchlib->buildQuery([]);

        if (is_numeric($input->start->string())) {
            $useTimestamp = true;
            $from = $input->start->int();
            $to = $input->end->int();
        } else {
            $useTimestamp = false;
            $timezone = $input->timezone->string();
            preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $input->start->string(), $matches);

            if ($input->start->string() === $matches[0]) {
                $from = strtotime($input->start->iso8601() . ' ' . $timezone);
                $to = strtotime($input->end->iso8601() . ' ' . $timezone);
            } else {
                $from = strtotime($input->start->isodate());
                $to = strtotime($input->end->isodate());
            }
        }

        $query->filterRange(0, $to, $start);
        $query->filterRange($from, $to + 1000 * 365 * 86400, $end);
        $maxRecords = $input->maxRecords->int() ?: null;
        $query->setRange(0, $maxRecords);

        $innerMatches = [];
        if ($body = $input->filters->none()) {
            $builder = new Search_Query_WikiBuilder($query, $input);
            $innerMatches = WikiParser_PluginMatcher::match($body);
            $builder->apply($innerMatches);
        }

        $result = $query->search($index);

        $response = [];

        $fields = [];
        if ($definition = Tracker_Definition::get($input->trackerId->int())) {
            foreach ($definition->getPopupFields() as $fieldId) {
                if ($field = $definition->getField($fieldId)) {
                    $fields[] = $field;
                }
            }
        }

        $hasFormat = false;
        foreach ($innerMatches as $match) {
            if ($match->getName() === 'format') {
                $hasFormat = true;
                break;
            }
        }
        if ($hasFormat) {
            // Note: reusing the ReportTemplate plugin leaves the field values alone and returns the
            // \Search_Formatter_ValueFormatter::getPlainValues version
            $plugin = new Search_Formatter_Plugin_ReportTemplate($body);

            $formatBuilder = new Search_Formatter_Builder();
            $formatBuilder->setId('wptrackercalendar-' . $id);
            $formatBuilder->setCount($result->count());
            $formatBuilder->apply($innerMatches);
            $formatBuilder->setFormatterPlugin($plugin);
            $formatter = $formatBuilder->getFormatter(isset($result->errorInQuery) && ! empty($result->errorInQuery));
            $entries = $formatter->getPopulatedList($result, false);
        } else {
            $entries = [];
        }

        $smarty = TikiLib::lib('smarty');
        $trklib = TikiLib::lib('trk');
        foreach ($result as $index => $row) {
            if ($entries) {
                $row = array_merge($row->getArrayCopy(), $entries[$index]);
            }
            $item = Tracker_Item::fromId($row['object_id']);

            if (empty($row['description'])) {
                $row['description'] = '';
                foreach ($fields as $field) {
                    if ($item->canViewField($field['fieldId'])) {
                        $val = trim(
                            $trklib->field_render_value(
                                [
                                    'field'   => $field,
                                    'item'    => $item->getData(),
                                    'process' => 'y',
                                ]
                            )
                        );
                        if ($val) {
                            if (count($fields) > 1) {
                                $row['description'] .= "<h5>{$field['name']}</h5>";
                            }
                            $row['description'] .= $val;
                        }
                    }
                }
            }

            $colormap = base64_decode($input->colormap->word());

            $dtStart = $this->getTimestamp($row[$start]);
            $dtEnd = $this->getTimestamp($row[$end]);

            $response[] = [
                'id'               => $row['object_id'],
                'trackerId'        => isset($row['tracker_id']) ? $row['tracker_id'] : null,
                'title'            => $row['title'],
                'description'      => $row['description'],
                'url'              => smarty_modifier_sefurl($row['object_id'], $row['object_type']),
                'allDay'           => false,
                'start'            => $useTimestamp ? $dtStart : TikiLib::date_format("c", $dtStart, $user, 5, false),
                'end'              => $useTimestamp ? $dtEnd : TikiLib::date_format("c", $dtEnd, $user, 5, false),
                'editable'         => $item->canModify(),
                'color'            => $this->getColor(isset($row[$coloring]) ? $row[$coloring] : '', $colormap),
                'textColor'        => '#000',
                'resourceId'       => ($resource && isset($row[$resource])) ? strtolower($row[$resource]) : '',
                'resourceEditable' => true,
            ];
        }

        return $response;
    }

    private function getTimestamp($value)
    {
        if (preg_match('/^\d{14}$/', $value)) {
            // Facing a date formated as YYYYMMDDHHIISS as indexed in lucene
            // Always stored as UTC
            return date_create_from_format('YmdHise', $value . 'UTC')->getTimestamp();
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value . ' UTC');
        }
    }

    private function getColor($value, $colormap)
    {
        static $colors = ['#6cf', '#6fc', '#c6f', '#cf6', '#f6c', '#fc6'];
        static $map = [];

        if (empty($map) && ! empty($colormap)) {
            foreach (explode('|', $colormap) as $color) {
                $colorMapParts = explode(',', $color);
                $map[trim($colorMapParts[0])] = trim($colorMapParts[1]);
            }
        }

        if (! isset($map[$value])) {
            $color = array_shift($colors);
            $colors[] = $color;
            $map[$value] = $color;
        }

        return $map[$value];
    }
}
