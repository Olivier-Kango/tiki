<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Calendar_Controller
{
    private CalendarLib $calendarLib;
    private \Tiki\Lib\Logs\LogsLib $logsLib;
    private $daynamesPlural;
    private $monthnames;
    private $daynames;

    public function setUp(): void
    {
        global $prefs;

        Services_Exception_Disabled::check('feature_calendar');

        $this->calendarLib = TikiLib::lib('calendar');
        if ($prefs['feature_actionlog'] == 'y') {
            $this->logsLib = TikiLib::lib('logs');
        }

        $this->daynamesPlural = [
            'SU' => tra('Sundays'),
            'MO' => tra('Mondays'),
            'TU' => tra('Tuesdays'),
            'WE' => tra('Wednesdays'),
            'TH' => tra('Thursdays'),
            'FR' => tra('Fridays'),
            'SA' => tra('Saturdays'),
        ];
        $this->monthnames = [
            '',
            tra('January'),
            tra('February'),
            tra('March'),
            tra('April'),
            tra('May'),
            tra('June'),
            tra('July'),
            tra('August'),
            tra('September'),
            tra('October'),
            tra('November'),
            tra('December'),
        ];
        $this->daynames = [
            'SU' => tra('Sunday'),
            'MO' => tra('Monday'),
            'TU' => tra('Tuesday'),
            'WE' => tra('Wednesday'),
            'TH' => tra('Thursday'),
            'FR' => tra('Friday'),
            'SA' => tra('Saturday'),
        ];
    }

    /**
     * Returns the section for use with certain features like banning
     *
     * @return string
     */
    public function getSection()
    {
        return 'calendar';
    }

    public function action_move($input)
    {
        $itemId = $this->getItemId($input);
        $delta = $input->delta->int();

        $calendarlib = TikiLib::lib('calendar');
        $calendarlib->move_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
    }

    public function action_resize($input)
    {
        $itemId = $this->getItemId($input);
        $delta = $input->delta->int();

        $calendarlib = TikiLib::lib('calendar');
        $calendarlib->resize_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
    }

    /**
     * Edits or creates a calendar item (event)
     *
     * @param JitFilter $input
     *
     * @return array
     * @throws Services_Exception_NotFound
     */

    public function action_edit_item(JitFilter $input): array
    {
        global $user, $prefs;
        $access = TikiLib::lib('access');
        $parserLib = TikiLib::lib('parser');
        $displayTimezone = TikiLib::lib('tiki')->get_display_timezone();

        $rawcals = $this->calendarLib->list_calendars();

        if ($rawcals['cant'] === 0) {
            throw new Services_Exception_NotFound(tr('No calendars found'));
        }

        $calendars = Perms::filter(
            ['type' => 'calendar'],
            'object',
            $rawcals['data'],
            ['object' => 'calendarId'],
            ['add_events', 'change_events']
        );

        $calitemId = $input->calitemId->int();
        $preview = false;

        if ($access->requestIsPost() && $access->checkCsrf(false, true)) {
            if ($input->offsetExists('calitem')) {
                $calitem = $input->asArray('calitem');
                $calendarId = $calitem['calendarId'];
                $calitem['calitemId'] = $calitemId;
                $calitem['allday'] = empty($calitem['allday']) ? 0 : 1;
                $calitem['recurrenceId'] = $input->recurrenceId->int();
                $calendar = $this->calendarLib->get_calendar($calendarId);

                // save event
                if ($input->act->word() === 'save' || $input->act->word() === 'saveas') {
                    $calitemId = $this->saveEvent($calitem, $calendar, $input);

                    if ($calitemId) { // then redirect?
                        if ($input->offsetExists('redirect')) {
                            return ['url' => $input->redirect->url()];
                        } else {
                            // reload the page?
                            return [];
                        }
                    } else {
                        Feedback::error(tr('Calendar edit error')); // TODO more
                    }
                } else {
                    $title = $calitem['title'];

                    $preview = $input->act->word() === 'preview';

                    $calitem['parsed'] = $parserLib->parse_data(
                        $calitem['description'],
                        ['is_html' => $prefs['calendar_description_is_html'] === 'y']
                    );
                    $calitem['parsedName'] = $parserLib->parse_data($calitem['name']);
                }
            } else {
                Feedback::error(tr('No event data?'));
                return [];
            }
        } elseif ($calitemId) {
            // load from database
            $calitemId = $this->getItemId($input);  // also checks edit perms
            $calitem = $this->calendarLib->get_item($calitemId);
            $calendarId = $calitem['calendarId'];
            $calendar = $this->calendarLib->get_calendar($calendarId);
            $title = tr('Calendar event : %0', $calitem['name']);
        } else {
            // new event
            $title = tr('Calendar event : %0', tr('New'));
            $calitemId = 0;
            $calendar = $calendars[0];
            $calendarId = $calendar['calendarId'];

            // set up default start and end
            $date = new TikiDate();
            $date->setTZbyID($displayTimezone);
            $hour = $date->date->format('H');
            if ($input->offsetExists('todate')) {
                // set the correct day clicked on
                $date->setDate($input->todate->int());
            }
            $start = mktime(
                $hour,
                0,
                0,
                $date->date->format('m'),
                $date->date->format('d'),
                $date->date->format('Y')
            );
            $duration = 60 * 60;
            $end = $start + $duration;

            $calitem = [
                'calitemId'             => $calitemId,
                'calendarId'            => $calendarId,
                'user'                  => $user,
                'name'                  => '',
                'url'                   => '',
                'description'           => '',
                'status'                => $calendar['defaulteventstatus'],
                'priority'              => 0,
                'locationId'            => 0,
                'categoryId'            => 0,
                'nlId'                  => 0,
                'start'                 => $start,
                'end'                   => $end,
                'duration'              => $duration,
                'recurrenceId'          => 0,
                'allday'                => $calendar['allday'] == 'y' ? 1 : 0,
                'organizers'            => [$user],
                'participants'          => [[
                                                'username' => $user,
                                                'role'     => '',
                                                'partstat' => '',
                                            ]],
                'selected_participants' => [$user],
            ];
        }

        if (isset($calitem['recurrenceId']) && $calitem['recurrenceId'] > 0) {
            $recurrence = new CalRecurrence($calitem['recurrenceId']);
            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = (int)TikiDb::get()->table('tiki_calendar_items')->fetchCount([
                'recurrenceId' => $calitem['recurrenceId'],
                'changed'      => 1,
            ]);
        } else {
            $recurrence = new CalRecurrence();
            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = 0;
        }

        $showeachuser = '';
        $listusertoalert = [];
        $groupforalert = '';

        if ($prefs['feature_groupalert'] === 'y' && ! empty($calendarId)) {
            $groupalertlib = TikiLib::lib('groupalert');

            $groupforalert = $groupalertlib->GetGroup('calendar', $calendarId);
            if ($groupforalert != '') {
                $showeachuser = $groupalertlib->GetShowEachUser('calendar', $calendarId, $groupforalert);
                $listusertoalert = TikiLib::lib('user')->get_users(0, -1, 'login_asc', '', '', false, $groupforalert, '');
                $listusertoalert = $listusertoalert['data'];
            }
        }

        if (! $input->modal->int()) {
            TikiLib::lib('header')
                ->add_cssfile('themes/base_files/feature_css/calendar.css', 20)
                ->add_jsfile('lib/jquery_tiki/calendar_edit_item.js');
        }

        return [
            // collections
            'daynames'                   => $this->daynames,
            'monthnames'                 => $this->monthnames,
            'calendars'                  => $calendars,
            // event info
            'title'                      => $title,
            'calitem'                    => $calitem,
            'calitemId'                  => $calitemId,
            'calendar'                   => $calendar,
            'calendarId'                 => $calendarId,
            // modes
            'edit'                       => true,
            'preview'                    => $preview,
            // recurrences
            'recurrence'                 => $recurrence,
            'recurranceNumChangedEvents' => $recurranceNumChangedEvents,
            // "custom" calendar properties
            'listcats'                   => $this->calendarLib->list_categories($calendarId),
            'listlocs'                   => $this->calendarLib->list_locations($calendarId),
            // group alert info
            'listusertoalert'            => $listusertoalert,
            'groupforalert'              => $groupforalert,
            'showeachuser'               => $showeachuser,
            'modal'                      => $input->modal->int(),
        ];
    }

    public function action_copy_item(JitFilter $input): array
    {
        $input->offsetSet('calitemId', 0);

        return $this->action_edit_item($input);
    }

    /**
     * Shows a calendar item (event)
     *
     * @param JitFilter $input
     *
     * @return array
     */

    public function action_view_item(JitFilter $input): array
    {


        $calitemId = $input->calitemId->int();

        if ($calitemId) {
            $calitemId = $this->getItemId($input, 'view_events');  // also checks edit perms
            $calitem = $this->calendarLib->get_item($calitemId);
        } elseif ($input->preview->word() === tr('Preview')) {
            $calitem = $input->asArray('calitem');
        } else {
            Feedback::error(tr('Not found'));
            $calitem = [];
        }
        return [
            'calitem'    => $calitem,
            'daynames'   => $this->daynamesPlural,
            'monthnames' => $this->monthnames,
        ];
    }

    public function action_delete_item(JitFilter $input): array
    {
        global $user;

        $calitemId = $this->getItemId($input); // also checks edit perms

        if ($calitemId) {
            $this->calendarLib->drop_item($user, $calitemId);
            if ($this->logsLib) {
                $this->logsLib->add_action('Removed', 'event ' . $_REQUEST['calitemId'], 'calendar event');
            }
        }

        return [];
    }

    public function action_delete_recurrent_items(JitFilter $input): array
    {
        global $user;

        $calitemId = $this->getItemId($input); // also checks edit perms

        if ($calitemId && $input->recurrenceId->int()) {
            $calRec = new CalRecurrence($input->recurrenceId->int());
            $calRec->delete();
            if ($this->logsLib) {
                $this->logsLib->add_action('Removed', 'recurrent event (recurrenceId = ' . $_REQUEST["recurrenceId"] . ')', 'calendar event');
            }
        }

        return [];
    }

    /**
     * @param array     $calitem  new data for the event
     * @param array     $calendar calendar it belongs to
     * @param JitFilter $input    the whole input
     *
     * @return int
     */
    private function saveEvent(array $calitem, array $calendar, JitFilter $input): int
    {
        global $prefs;
        $displayTimezone = TikiLib::lib('tiki')->get_display_timezone();

        $eventDefaults = [
            'name'     => tra("Event without name"),
            'priority' => 1,
            'status'   => $calendar['defaulteventstatus'] ?? 1,
        ];

        $calitem = array_merge($eventDefaults, $calitem);

        if ($calitem['end'] < $calitem['start']) {
            $impossibleDates = true;
        } elseif ($calitem['start'] + (24 * 60 * 60) < $calitem['end']) { // more than a day?
            $impossibleDates = true;
        } else {
            $impossibleDates = false;
        }

        if (! $impossibleDates) {
            if ($input->recurrent->int() && $input->affect->word() !== 'event') {
                $calRecurrence = new CalRecurrence(
                    ! empty($input->recurrenceId->int()) ? $input->recurrenceId->int() : -1
                );
                $calRecurrence->setCalendarId($calitem['calendarId']);
                $tz = date_default_timezone_get();
                date_default_timezone_set('UTC');
                $tikidateStart = new TikiDate();
                $tikidateStart->setDate($calitem['start']);
                $tikidateEnd = new TikiDate();
                $tikidateEnd->setDate($calitem['end']);
                $calRecurrence->setStart($tikidateStart->format("%H%M", true));
                $calRecurrence->setEnd($tikidateEnd->format("%H%M", true));
                date_default_timezone_set($tz);
                $calRecurrence->setAllday($calitem['allday']);
                $calRecurrence->setLocationId($calitem['locationId']);
                $calRecurrence->setCategoryId($calitem['categoryId']);
                $calRecurrence->setNlId(0); //TODO : What id nlId ?
                $calRecurrence->setPriority($calitem['priority']);
                $calRecurrence->setStatus($calitem['status']);
                $calRecurrence->setUrl($calitem['url']);
                $calRecurrence->setLang(strLen($calitem['lang']) > 0 ? $calitem['lang'] : 'en');
                $calRecurrence->setName($calitem['name']);
                $calRecurrence->setDescription($calitem['description']);
                switch ($input->recurrenceType->word()) {
                    case "weekly":
                        $calRecurrence->setWeekly(true);
                        $calRecurrence->setWeekdays(implode(',', $input->asArray('weekdays')));
                        $calRecurrence->setMonthly(false);
                        $calRecurrence->setYearly(false);
                        break;
                    case "monthly":
                        $calRecurrence->setWeekly(false);
                        $calRecurrence->setMonthly(true);
                        $calRecurrence->setYearly(false);
                        $calRecurrence->setMonthlyType($input->recurrenceTypeMonthy->word());
                        if ($input->recurrenceTypeMonthy->word() && $input->recurrenceTypeMonthy->word() === 'weekday') {
                            $monthlyWeekdayValue = $input->weekNumberByMonth->word() . $input->monthlyWeekday->word();  // actually ints
                            $calRecurrence->setMonthlyWeekdayValue($monthlyWeekdayValue);
                        } else {
                            $calRecurrence->setDayOfMonth($input->dayOfMonth->word());
                        }
                        break;
                    case "yearly":
                        $calRecurrence->setWeekly(false);
                        $calRecurrence->setMonthly(false);
                        $calRecurrence->setYearly(true);
                        $calRecurrence->setDateOfYear(
                            str_pad($input->dateOfYear_month->word(), 2, '0', STR_PAD_LEFT) .
                            str_pad($input->dateOfYear_day->word(), 2, '0', STR_PAD_LEFT)
                        );
                        break;
                }
                // startPeriod does not exist when using the old non-jscalendar time selector with 3 dropdowns
                $startPeriod = $input->startPeriod->int();
                if (empty($startPeriod)) {
                    $startPeriod = mktime(
                        0,
                        0,
                        0,
                        $input->startPeriod_Month->int(),
                        $input->startPeriod_Day->int(),
                        $input->startPeriod_Year->int()
                    );
                }
                if ($calRecurrence->getId() > 0 && $calitem['calitemId'] == $calRecurrence->getFirstItemId()) {
                    // modify start period when the first event is updated
                    $calRecurrence->setStartPeriod(TikiDate::getStartDay($calitem['start'], $displayTimezone));
                } else {
                    $calRecurrence->setStartPeriod($startPeriod);
                }
                if ($input->endType->word() === "dt") {
                    $calRecurrence->setEndPeriod($input->endPeriod->word());
                } else {
                    $nbRecurrences = $input->nbRecurrences->int() ?? 1;
                    if ($input->recurrenceType->word() === 'weekly') {
                        $nbRecurrences = $nbRecurrences * count($input->asArray('weekdays'));
                    }
                    $calRecurrence->setNbRecurrences($nbRecurrences);
                }
                $calRecurrence->setUser($calitem['user']);
                if (! empty($calitem['calitemId'])) {
                    // store the initial event if it was already created
                    $calRecurrence->setInitialItem($calitem);
                }
                $calRecurrence->save(! empty($input->affect->word()) && $input->affect->word() === 'all');

                if ($this->logsLib) {
                    $this->logsLib->add_action(
                        (empty($calitem['calitemId']) ? 'Created' : 'Updated') .
                            'event ' . $calitem['calitemId'] . ' in calendar ' . $calitem['calendarId'],
                        'calendar event'
                    );
                }
            } else {
                if ($input->offsetExists('recurrenceId')) {
                    $calitem['recurrenceId'] = $input->recurrenceId->int();
                    $calitem['changed'] = 1;
                }

                global $user;
                $calitemId = $this->calendarLib->set_item($user, $calitem['calitemId'] ?? 0, $calitem);
                // Save the ip at the log for the addition of new calendar items
                if ($this->logsLib) {
                    $this->logsLib->add_action(
                        (empty($calitem['calitemId']) ? 'Created' : 'Updated') .
                            'event ' . $calitemId . ' in calendar ' . $calitem['calendarId'],
                        'calendar event'
                    );
                }
                if ($prefs['feature_groupalert'] == 'y') {
                    if ($input->offsetExists('listtoalert')) {
                        TikiLib::lib('groupalert')->Notify(
                            $input->listtoalert->int(),
                            "tiki-calendar-view_item?calitemId=" . $calitemId
                        );
                    }
                }
                return $calitemId;
            }
        }
    }

    /**
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */
    private function getItemId(JitFilter $input, string $perm = 'change_events'): int
    {
        $item = $input->calitemId->int();

        $cal_id = $this->calendarLib->get_calendarid($item);

        if (! $item || ! $cal_id) {
            throw new Services_Exception_NotFound();
        }

        $calperms = Perms::get(['type' => 'calendar', 'object' => $cal_id]);
        if (! $calperms->$perm) {
            throw new Services_Exception_Denied();
        }

        return $item;
    }
}
