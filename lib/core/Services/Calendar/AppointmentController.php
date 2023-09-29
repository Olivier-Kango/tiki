<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\SabreDav\CaldavClient;
use Sabre\VObject\Recur;

class Services_Calendar_AppointmentController extends Services_Calendar_BaseController
{
    public function action_slots($input)
    {
        global $user;

        $target_user = $input->user->text();
        $uid = $input->uid->text();

        if (! TikiLib::lib('user')->user_exists($target_user)) {
            throw new Services_Exception_Denied(tr('Invalid appointment slot URL.'));
        }

        $client = new CaldavClient();
        $result = $client->getAvailability($target_user);
        $vavail = null;

        if ($result) {
            foreach ($result->VAVAILABILITY as $vavailability) {
                if (strval($vavailability->uid) === $uid) {
                    $vavail = $vavailability;
                    break;
                }
            }
        }

        if (! $vavail) {
            throw new Services_Exception_Denied(tr('Invalid appointment slot URL.'));
        }

        $calendarId = strval($vavail->{'X-Tiki-CalendarId'});
        $perms = Perms::get('calendar', $calendarId);

        if (! $perms->add_events) {
            throw new Services_Exception_Denied();
        }

        $start = new DateTime();
        $start->setTimestamp(strtotime('-1 day'));
        $end = new DateTime();
        $end->setTimestamp(strtotime('+3 months'));

        list($vavailStart, $vavailEnd) = $vavail->getEffectiveStartEnd();
        if (! $vavailStart || $vavailStart < $start) {
            $vavailStart = $start;
        }
        if (! $vavailEnd || $vavailEnd > $end) {
            $vavailEnd = $end;
        }

        // TODO: should we count only confirmed events/appointments here?
        $my_events = TikiLib::lib('calendar')->busyTimesFromCalendar($calendarId, $vavailStart->getTimestamp(), $vavailEnd->getTimestamp(), $target_user);

        $slots = [];
        if (isset($vavail->AVAILABLE)) {
            foreach ($vavail->AVAILABLE as $available) {
                if (empty($available->{'X-Tiki-Slots'})) {
                    continue;
                }
                list($availStart, $availEnd) = $available->getEffectiveStartEnd();

                if ($availStart >= $vavailStart && $availStart <= $vavailEnd) {
                    $slots = array_merge($slots, $this->expandSlots($availStart, min($availEnd, $vavailEnd), strval($available->{'X-Tiki-Slots'})));
                }

                if ($available->RRULE) {
                    $rruleIterator = new Recur\RRuleIterator(
                        $available->RRULE->getValue(),
                        $availStart
                    );
                    $rruleIterator->fastForward($vavailStart);

                    $startEndDiff = $availStart->diff($availEnd);

                    while ($rruleIterator->valid()) {
                        $recurStart = $rruleIterator->current();
                        $recurEnd = $recurStart->add($startEndDiff);

                        if ($recurStart > $vavailEnd) {
                            // We're beyond the legal timerange.
                            break;
                        }

                        if ($recurEnd > $vavailEnd) {
                            // Truncating the end if it exceeds the VAVAILABILITY end.
                            $recurEnd = $vavailEnd;
                        }

                        $slots = array_merge($slots, $this->expandSlots($recurStart, $recurEnd, strval($available->{'X-Tiki-Slots'})));

                        $rruleIterator->next();
                    }
                }
            }
        }

        $slots = array_unique($slots, SORT_REGULAR);
        $slots = array_map(function ($slot) use ($my_events) {
            $free = true;
            foreach ($my_events as $event) {
                if ($event['start'] >= $slot['start'] && $event['start'] < $slot['end']) {
                    $free = false;
                    break;
                }
                if ($event['end'] > $slot['start'] && $event['end'] <= $slot['end']) {
                    $free = false;
                    break;
                }
            }
            $slot['free'] = $free;
            return $slot;
        }, $slots);
        sort($slots, SORT_REGULAR);

        $dates = [];
        foreach ($slots as $slot) {
            $date = TikiLib::lib('tiki')->date_format('%Y-%m-%d', $slot['start']);
            if (! in_array($date, $dates)) {
                $dates[] = $date;
            }
        }

        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');

        return [
            'title' => strval($vavail->SUMMARY),
            'description' => strval($vavail->DESCRIPTION),
            'dates' => $dates,
            'slots' => $slots,
            'calendarId' => $calendarId,
            'target_user' => $target_user,
            'user' => $user,
            'uid' => $uid,
            'embed' => $input->embed->int(),
            'layout_name' => $input->embed->int() ? "layout_embed.tpl" : "layout_view.tpl",
            'timezone' => TikiLib::lib('tiki')->get_display_timezone(),
        ];
    }

    protected function expandSlots($start, $end, $duration)
    {
        $result = [];
        $iv = new DateInterval("PT{$duration}M");

        $current = $start;
        while ($current < $end) {
            $slot = [
                'start' => $current->getTimestamp()
            ];
            $current = $current->add($iv);
            $slot['end'] = $current->getTimestamp();
            if ($slot['start'] >= time()) {
                $result[] = $slot;
            }
        }

        return $result;
    }
}
