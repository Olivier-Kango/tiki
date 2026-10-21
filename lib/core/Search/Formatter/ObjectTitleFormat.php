<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Search\Formatter;

/**
 * Renders a search result label from a format like "{tracker_field_Priority}: {title}".
 */
class ObjectTitleFormat
{
    public const DEFAULT_FORMAT = '{title}';

    public static function render(string $format, $item, bool $raw = false, ?callable $valueFilter = null): string
    {
        return preg_replace_callback('/\{([\w\.]+)\}/', function ($matches) use ($item, $format, $raw, $valueFilter) {
            $key = $matches[1];
            if (isset($item[$key])) {
                // if this is a trackeritem we do not want only the name but also the trackerid listed when setting up a field
                // otherwise its hard to distingish which field that is if multiple tracker use the same fieldname
                // example: setup of trackerfield item-link: choose some fields from a list. currently this list show all fields of all trackers
                if (($item['object_type'] ?? '') == 'trackerfield') {
                    return $item[$key] . ' (Tracker-' . $item['tracker_id'] . ', Field-' . $item['object_id'] . ')';
                } elseif ($raw) {
                    // Caller explicitly asked for the exact stored value for this
                    // token (e.g. an ItemLink's raw itemId), so skip the "_text"
                    // rendered-label substitution below entirely.
                    $value = $item[$key];
                    return is_array($value) ? implode(',', $value) : $value;
                } else {
                    $value = $item[$key];
                    if (is_array($value)) {
                        $value = $item[$key . "_paths"] ??
                            $item[$key . "_names"] ??
                            $item[$key . "_text"] ??
                            implode(',', $item[$key]);
                    } elseif (! empty($item[$key . '_text'])) {
                        $value = $item[$key . '_text'];
                    }
                    if ($valueFilter) {
                        $value = $valueFilter($value);
                    }
                    return $value;
                }
            } elseif (str_starts_with($key, 'meta.')) {
                return '';
            } elseif ($format == self::DEFAULT_FORMAT) {
                return tr('empty');
            } else {
                return '';
            }
        }, $format);
    }
}
