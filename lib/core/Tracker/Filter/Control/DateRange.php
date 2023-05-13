<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Filter\Control;

class DateRange implements Control
{
    private $fieldName;
    private $from = '';
    private $to = '';

    public function __construct($name)
    {
        $this->fieldName = $name;
    }

    public function applyInput(\JitFilter $input)
    {
        $this->from = $input->{$this->fieldName . '_from'}->int() ?: '';
        $this->to = $input->{$this->fieldName . '_to'}->int() ?: '';

        if ($tzname = $input->tzname->text()) {
            try {
                $dtz = new \DateTimeZone($tzname);
            } catch (\Exception $e) {
                \Feedback::error(tr('Error using local timezone %0: %1', $tzname, $e->getMessage()));
                return;
            }
            if ($this->from) {
                $dt = new \DateTime('@' . $this->from);
                $dt->setTimeZone($dtz);
                $this->from += $dt->getOffset();
            }
            if ($this->to) {
                $dt = new \DateTime('@' . $this->to);
                $dt->setTimeZone($dtz);
                $this->to += $dt->getOffset();
            }
        } elseif ($tzoffset = $input->tzoffset->int()) {
            $browser_offset = (int)$tzoffset * 60;
            if ($this->from) {
                $this->from -= $browser_offset;
            }
            if ($this->to) {
                $this->to -= $browser_offset;
            }
        }
    }

    public function getQueryArguments()
    {
        if ($this->from && $this->to) {
            return [
                $this->fieldName . '_from' => $this->from,
                $this->fieldName . '_to' => $this->to,
            ];
        } else {
            return [];
        }
    }

    public function getDescription()
    {
        if ($this->hasValue()) {
            $tikilib = \TikiLib::lib('tiki');
            return tr(
                'From %0 to %1',
                $tikilib->get_short_date($this->from),
                $tikilib->get_short_date($this->to)
            );
        } else {
            return '';
        }
    }

    public function getId()
    {
        return $this->fieldName . '_from';
    }

    public function isUsable()
    {
        return true;
    }

    public function hasValue()
    {
        return ! empty($this->from) && ! empty($this->to);
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        // Date ranges are inclusive, so use end of day so last day is included
        return $this->to + 3600 * 24 - 1;
    }

    public function __toString()
    {
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('control', [
            'field' => $this->fieldName,
            'from' => $this->from,
            'to' => $this->to,
        ]);
        return $smarty->fetch('filter_control/date_range.tpl');
    }
}
