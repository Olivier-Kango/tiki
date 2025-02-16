<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\TikiInit;

require_once('tiki-setup.php');

$access->check_feature('feature_calendar');
$access->check_permission('tiki_p_view_events');

// Initialization
TikiInit::appendIncludePath("lib/ical/");
include_once('lib/ical/iCal.php');

// list calendars //
$calendarlib = TikiLib::lib('calendar');

// ###trebly:B10111:[FIX-ADD-ENH]->  there are several meaning for the same var $calendarViewMode
if (! isset($calendarViewMode)) {
// ###trebly:B10111:[FIX-ADD-ENH]-> $calendarViewMode become an array, several bugs comes from confusion of global values and parameters by ref
// for calendars : (main-)calendar, action_calendar, mod_calendar, mod_action_calendar the changes of values by url request is terrible
// for the moment 01/11/2011:11:55 just one value is used with index 'default', but initialisation is done.
// The init is actually into two places, tiki-calendar_setup.php and tiki-calendar_export.php will be grouped for clean
// $prefs would be added when need, $_SESSION, $PARAMS too this now generates not any change in the behavior.
    $calendarViewMode = ['casedefault' => 'month','calgen' => 'month','calaction' => 'month','modcalgen' => 'month','modcalaction' => 'month','trackercal' => 'month'];
    if (! empty($_REQUEST['viewmode'])) {
        $calendarViewMode['casedefault'] = $_REQUEST['viewmode'];
    } elseif (! empty($_SESSION['CalendarViewMode'])) {
        $calendarViewMode['casedefault'] = $_SESSION['CalendarViewMode'];
    } else {
        $calendarViewMode['casedefault'] = $prefs['calendar_view_mode'];
    }
}

# If specified, limit the export to the maximum number of records (events)
# indicated in the request; otherwise, the limit is from the global preferences.
if (isset($_REQUEST['maxRecords'])) {
        $maxRecords = $_REQUEST['maxRecords'];
}

if (isset($_SESSION['CalendarFocusDate'])) {
    $startTime = $_SESSION['CalendarFocusDate'];
} else {
// by default, export will start from yesterday's events.
    $startDate = new TikiDate();
    $startDate->addDays(-1);
    $startTime = $startDate->getTime();
}

if (isset($_REQUEST['start_date_Month'])) {
    $startTime = TikiLib::make_time(0, 0, 0, $_REQUEST['start_date_Month'], $_REQUEST['start_date_Day'], $_REQUEST['start_date_Year']);
} elseif (isset($_REQUEST["tstart"])) {
    $startTime = $_REQUEST["tstart"];
}

$endDate = new TikiDate();
$endDate->setDate($startTime);
if ($calendarViewMode['casedefault'] == 'month') {
     $stopTime = $endDate->addMonths(1);
} elseif ($calendarViewMode['casedefault'] == 'quarter') {
    $stopTime = $endDate->addMonths(3);
} elseif ($calendarViewMode['casedefault'] == 'semester') {
    $stopTime = $endDate->addMonths(6);
} elseif ($calendarViewMode['casedefault'] == 'year') {
    $stopTime = $endDate->addMonths(12);
} else {
    $stopTime = $endDate->addMonths(1);
}
$stopTime = $endDate->getTime();

if (isset($_REQUEST['stop_date_Month'])) {
    $stopTime = TikiLib::make_time(0, 0, 0, $_REQUEST['stop_date_Month'], $_REQUEST['stop_date_Day'], $_REQUEST['stop_date_Year']);
} elseif (isset($_REQUEST["tstop"])) {
    $stopTime = $_REQUEST["tstop"];
}

$calendarIds = [];
if (isset($_REQUEST['calendarIds'])) {
    $calendarIds = $_REQUEST['calendarIds'];
    foreach ($calendarIds as $anId) {
        $smarty->assign('individual_' . $anId, $userlib->object_has_one_permission($anId, 'calendar'));
    }
} else {
    if (! isset($_REQUEST["calendarId"])) {
        $_REQUEST["calendarId"] = 0;
    } else {
         $smarty->assign('individual_' . $_REQUEST["calendarId"], $userlib->object_has_one_permission($_REQUEST["calendarId"], 'calendar'));
    }
}
$sort_mode = "name";

$find = "";
$calendars = $calendarlib->list_calendars(0, -1, $sort_mode, $find);

foreach (array_keys($calendars["data"]) as $i) {
    $calendars["data"][$i]["individual"] = $userlib->object_has_one_permission($i, 'calendar');
}
$smarty->assign('calendars', $calendars["data"]);

// export calendar //
if (((is_array($calendarIds) && (count($calendarIds) > 0)) or isset($_REQUEST["calendarItem"]) ) && $_REQUEST["export"] == 'y') {
    // get calendar events
    if (! isset($_REQUEST["calendarItem"])) {
        $events = $calendarlib->list_raw_items($calendarIds, $user, $startTime, $stopTime, -1, $maxRecords, $sort_mode = 'start_asc', $find = '');
    } else {
        $events = $calendarlib->getItemWithRecurrence($_REQUEST["calendarItem"]);
    }

    if (isset($_REQUEST['csv'])) {
        header('Content-type: text/csv');
        header("Content-Disposition: inline; filename=tiki-calendar.csv");
        $first = true;
        $description = '';
        foreach ($events as $event) {
            $line = '';
            foreach ($event as $name => $field) {
                if ($first === true) {
                    $description .= '"' . $name . '";';
                }
                if (is_array($field)) {
                    $line .= '"' . str_replace(["\n","\r",'"'], ['\\n','','""'], join(',', $field)) . '";';
                } else {
                    $line .= '"' . str_replace(["\n","\r",'"'], ['\\n','','""'], $field) . '";';
                }
            }
            if ($first === true) {
                echo (trim($description, ';')) . "\n";
                $first = false;
            }
            echo trim($line, ';') . "\n";
        }
    } else {
        // create ical

        $userlb = TikiLib::get('Users');

        $vcalendar = new Sabre\VObject\Component\VCalendar();

        foreach ($events as $event) {
            $vevent = [];
            $vevent['SUMMARY'] = $event['name'];
            $vevent['DTSTART'] = (new DateTime())->setTimestamp($event['start']);
            $vevent['DTEND'] = (new DateTime())->setTimestamp($event['end']);

            $vevent['DESCRIPTION'] = preg_replace(
                '/\n/',
                "\\n",
                strip_tags(
                    TikiLib::lib('parser')->parse_data(
                        $event['description'],
                        ['is_html' => $prefs['calendar_description_is_html'] === 'y']
                    )
                )
            );

            $vevent['DTSTAMP'] = (new DateTime())->setTimestamp($event['created']);
            $vevent['LAST-MODIFIED'] = (new DateTime())->setTimestamp($event['lastModif']);

            $vevent['CONTACT'] = $event['user']; // Name

            if (! empty($event['url'])) {
                $vevent['URL'] = $event['url'];
            }

            $vevent['UID'] = 'tiki-' . $event['calendarId'] . '-' . $event['calitemId'];

            $vcalEvent = $vcalendar->add('VEVENT', $vevent);

            foreach ($event['organizers'] as $organizer) {
                $orgEmail = $userlib->get_user_email($organizer);

                if (! empty($orgEmail)) {
                    $vcalEvent->add('ORGANIZER', $orgEmail, ['CN' => $organizer]);
                } elseif (filter_var($organizer, FILTER_VALIDATE_EMAIL) !== false) {
                    $vcalEvent->add('ORGANIZER', $organizer);
                }
            }

            foreach ($event['participants'] as $attendee) {
                $attendeeEmail = $userlib->get_user_email($attendee['name']);

                if (! empty($attendeeEmail)) {
                    $vcalEvent->add('ATTENDEE', $attendeeEmail, ['CN' => $attendee['name']]);
                } else {
                    if (filter_var($attendee['name'], FILTER_VALIDATE_EMAIL) !== false) {
                        $vcalEvent->add('ATTENDEE', $attendee);
                    }
                }
            }
        }

        $calendar_str = $vcalendar->serialize();
        header("Content-Length: " . strlen($calendar_str));
        header("Expires: 0");
        // These two lines fix pb with IE and HTTPS
        header("Cache-Control: private");
        header("Pragma: dummy=bogus");
        // Outlook needs iso8859 encoding
        header("Content-Type:text/calendar; method=REQUEST; charset=iso-8859-15");
        header('Content-Disposition: inline; filename=tiki-calendar.ics');
        header("Content-Transfer-Encoding:quoted-printable");
        $re_encode = stripos($_SERVER['HTTP_USER_AGENT'], 'windows');   // only re-encode to ISO-8859-15 if client on Windows
        if (function_exists('iconv') && $re_encode !== false) {
            print(iconv("UTF-8", "ISO-8859-15", $calendar_str));
        } else {
            print($calendar_str);   // UTF-8 is good for other platforms
        }
    }
    die;
}


$smarty->assign('iCal', $iCal);

// Display the template
$smarty->display("tiki.tpl");
