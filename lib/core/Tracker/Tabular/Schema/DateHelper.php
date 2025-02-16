<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Schema;

class DateHelper
{
    private $label;
    private $prior = [];

    public function __construct($label)
    {
        $this->label = $label;
    }

    public function setupUnix(Column $column)
    {
        $permName = $column->getField();
        $this->setupCallbacks(
            $column,
            function ($value, array $extra) {
                if (empty($value)) {
                    return '';
                }

                return $this->convertToUnix($value);
            },
            function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            }
        );
    }

    public function setupFormat($format, Column $column)
    {
        $permName = $column->getField();
        $this->setupCallbacks(
            $column,
            function ($value, array $extra) use ($format) {
                if (empty($value) || $value === '0000-00-00 00:00:00') {    // empty dates are indexed as '0000-00-00 00:00:00'
                    return '';
                }

                $unix = $this->convertToUnix($value);
                return date($format, $unix);
            },
            function (&$info, $value) use ($permName, $format) {
                $date = date_create_from_format($format, $value);
                if (! $date) {
                    $date = date_create_from_format($format . '.v', $value);
                }
                if ($date) {
                    $timestamp = $date->getTimestamp();
                } else {
                    $tz = date_default_timezone_get();
                    if (substr($format, -1, 1) == 'e') {
                        // use default user's or Tiki timezone for this import
                        date_default_timezone_set(\TikiLib::lib('tiki')->get_display_timezone());
                    } else {
                        // use UTC for unknown time formats not specifying the time zone
                        date_default_timezone_set('UTC');
                    }
                    $timestamp = strtotime($value);
                    date_default_timezone_set($tz);
                }
                if ($timestamp) {
                    $info['fields'][$permName] = $timestamp;
                }
            }
        );
    }

    private function setupCallbacks($column, callable $render, callable $parseInto)
    {
        $column
            ->setLabel($this->label)
            ->setRenderTransform($render)
            ->setParseIntoTransform($parseInto)
            ;

        // Only one date field can control the value, add all previously added modes
        // as incompatibilities
        foreach ($this->prior as $mode) {
            $column->addIncompatibility($column->getField(), $mode);
        }

        $this->prior[] = $column->getMode();
    }

    private function convertToUnix($value)
    {
        if (preg_match('/^\d{14}$/', $value)) {
            return date_create_from_format('YmdHise', $value . 'UTC')->getTimestamp();
        } elseif (is_numeric($value)) {
            return (int) $value;
        } else {
            return strtotime($value);
        }
    }
}
