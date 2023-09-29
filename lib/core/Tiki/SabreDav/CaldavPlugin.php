<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SabreDav;

use DateTimeZone;
use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\VObject;

class CaldavPlugin extends CalDAV\Plugin
{
    /**
     * This method is responsible for parsing the request and generating the
     * response for the CALDAV:free-busy-query REPORT.
     * It includes the busy information from the requested calendar AND ALSO
     * takes into account the availability information of the calendar's user.
     */
    protected function freeBusyQueryReport(CalDAV\Xml\Request\FreeBusyQueryReport $report)
    {
        $uri = $this->server->getRequestUri();

        $acl = $this->server->getPlugin('acl');
        if ($acl) {
            $acl->checkPrivileges($uri, '{' . self::NS_CALDAV . '}read-free-busy');
        }

        $calendar = $this->server->tree->getNodeForPath($uri);
        if (! $calendar instanceof CalDAV\ICalendar) {
            throw new DAV\Exception\NotImplemented('The free-busy-query REPORT is only implemented on calendars');
        }

        $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';
        $inboxProp = '{' . self::NS_CALDAV . '}schedule-inbox-URL';
        $availabilityProp = '{' . self::NS_CALDAV . '}calendar-availability';
        $ownerProp = '{DAV:}owner';

        // Figuring out the default timezone for the calendar, for floating
        // times.
        $calendarProps = $this->server->getProperties($uri, [$tzProp, $ownerProp]);

        if (isset($calendarProps[$tzProp])) {
            $vtimezoneObj = VObject\Reader::read($calendarProps[$tzProp]);
            $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
            // Destroy circular references so PHP will garbage collect the object.
            $vtimezoneObj->destroy();
        } else {
            $calendarTimeZone = new DateTimeZone('UTC');
        }

        $availability = null;
        if (isset($calendarProps[$ownerProp])) {
            $ownerUrl = $calendarProps[$ownerProp]->getHref();
            $ownerProps = $this->server->getProperties($ownerUrl, [$inboxProp]);
            if (isset($ownerProps[$inboxProp])) {
                $inboxUrl = $ownerProps[$inboxProp]->getHref();
                $inboxProps = $this->server->getProperties($inboxUrl, $availabilityProp);
                if ($inboxProps) {
                    $availability = VObject\Reader::read($inboxProps[$availabilityProp]);
                }
            }
        }

        // Doing a calendar-query first, to make sure we get the most
        // performance.
        $urls = $calendar->calendarQuery([
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VEVENT',
                    'comp-filters' => [],
                    'prop-filters' => [],
                    'is-not-defined' => false,
                    'time-range' => [
                        'start' => $report->start,
                        'end' => $report->end,
                    ],
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ]);

        $objects = array_map(function ($url) use ($calendar) {
            $obj = $calendar->getChild($url)->get();

            return $obj;
        }, $urls);

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($report->start, $report->end);
        $generator->setTimeZone($calendarTimeZone);

        if ($availability) {
            $generator->setVAvailability($availability);
        }

        $result = $generator->getResult();
        $result = $result->serialize();

        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar');
        $this->server->httpResponse->setHeader('Content-Length', strlen($result));
        $this->server->httpResponse->setBody($result);
    }
}
