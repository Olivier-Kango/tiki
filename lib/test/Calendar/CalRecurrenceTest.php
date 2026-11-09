<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Calendar_CalRecurrenceTest extends TikiTestCase
{
    protected $calendarId;
    protected $recIds = [];

    protected function setUp(): void
    {
        global $user, $prefs;

        // FIXME These tests don't work if categories are enabled
        $prefs['feature_categories'] = 'n';

        $user = 'admin';
        $this->calendarId = TikiLib::lib('calendar')->set_calendar(0, $user, 'Test cal', '', ['customlanguages' => 'y', 'custompriorities' => 'y', 'customparticipants' => 'y']);
        $table = TikiLib::lib('tiki')->table('tiki_calendar_recurrence');
        $start = strtotime('12am');
        $this->recIds['daily'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Daily event',
            'daily' => 1,
            'days' => 1,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 days')),
        ]);
        $this->recIds['weekly'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Bi-weekly wednesday event',
            'weekly' => 1,
            'weeks' => 2,
            'weekdays' => 'WE',
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+9 weeks')),
        ]);
        $this->recIds['monthly-date'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Monthly 10th event',
            'monthly' => 1,
            'months' => 1,
            'monthlyType' => 'date',
            'dayOfMonth' => 10,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 months')),
        ]);
        $this->recIds['monthly-weekday'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Monthly every second tuesday event',
            'monthly' => 1,
            'months' => 1,
            'monthlyType' => 'weekday',
            'monthlyWeekdayValue' => '2TU',
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 months')),
        ]);
        $this->recIds['yearly-date'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Yearly Sep 15th ',
            'yearly' => 1,
            'years' => 1,
            'yearlyType' => 'date',
            'dateOfYear' => 915,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 years')),
        ]);
        $this->recIds['yearly-weekday'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Yearly every last friday of June',
            'yearly' => 1,
            'years' => 1,
            'yearlyType' => 'weekday',
            'yearlyWeekdayValue' => '-1FR',
            'yearlyWeekMonth' => 6,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime(date('Y-12-31', strtotime('+5 years')))),
        ]);
    }

    public function tearDown(): void
    {
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_recurrence");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_items");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_recurrence_exdates");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_roles");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendars WHERE calendarId = ?", $this->calendarId);
    }

    public function testLoad(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $this->assertEquals([
            'id' => $this->recIds['yearly-date'],
            'daily' => false,
            'days' => null,
            'weekly' => false,
            'weeks' => null,
            'weekdays' => [],
            'monthly' => false,
            'months' => null,
            'dayOfMonth' => [],
            'monthlyType' => null,
            'monthlyWeekdayValue' => null,
            'monthlyFirstlastWeekdayValue' => null,
            'yearly' => true,
            'years' => 1,
            'yearlyType' => 'date',
            'dateOfYear' => 915,
            'yearlyMonth' => 9,
            'yearlyDay' => 15,
            'yearlyWeekdayValue' => " ",
            'yearlyFirstlastWeekdayValue' => null,
            'yearlyWeekMonth' => null,
            'nbRecurrences' => null,
            'startPeriod' => $rec->getStartPeriod(),
            'startTimestamp' => $rec->getStartPeriod() + 12 * 3600,
            'endPeriod' => $rec->getEndPeriod(),
            'user' => '',
            'created' => 0,
            'lastModif' => 0,
            'recurrenceDstTimezone' => null,
        ], $rec->toArray());
    }

    public function testDailyValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $this->assertTrue($rec->isValid());
        $rec->setDaily(0);
        $this->assertFalse($rec->isValid());
    }

    public function testWeeklyValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $this->assertTrue($rec->isValid());
        $rec->setWeeks(0);
        $this->assertFalse($rec->isValid());
        $rec->setWeeks(2);
        $rec->setWeekdays('');
        $this->assertFalse($rec->isValid());
    }

    public function testMonthlyDateValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $this->assertTrue($rec->isValid());
        $rec->setDayOfMonth('1516');
        $this->assertFalse($rec->isValid());
    }

    public function testMonthlyWeekdayValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-weekday']);
        $this->assertTrue($rec->isValid());
        $rec->setMonthlyWeekdayValue('12SU');
        $this->assertFalse($rec->isValid());
    }

    public function testYearlyDateValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $this->assertTrue($rec->isValid());
        $rec->setDateOfYear('');
        $this->assertFalse($rec->isValid());
    }

    public function testYearlyWeekdayValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $this->assertTrue($rec->isValid());
        $rec->setYearlyWeekMonth('');
        $this->assertFalse($rec->isValid());
    }

    public function testFieldComparison(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $oldRec = new CalRecurrence($this->recIds['yearly-weekday']);
        $result = $rec->compareFields($oldRec);
        $this->assertEquals([
            'name', '_yearlyType', '_dateOfYear'
        ], $result);
        $result = $oldRec->compareFields($rec);
        $this->assertEquals([
            'name', '_yearlyType', '_yearlyWeekdayValue', '_yearlyWeekMonth'
        ], $result);
    }

    public function testEventCreationDaily(): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $this->assertEquals(5, count($events));
        $last = array_pop($events);
        $this->assertEquals(date('Y-m-d', strtotime('+4 days')), date('Y-m-d', $last['start']));
    }

    public function testEventCreationWeekly(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $this->assertTrue(in_array(count($events), [5,6]));
        $last = array_pop($events);
        $this->assertEquals(3, date('w', $last['start']));
    }

    public function testEventCreationMonthlyDate(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(10, date('j', $last['start']));
    }

    public function testEventCreationMonthlyWeekday(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $target = strtotime('next Tuesday', strtotime(date('Y-m-01', $last['start'])));
        if (date('N', strtotime(date('Y-m-01', $last['start']))) != '2') {
            $target = strtotime('next Tuesday', $target);
        }
        $this->assertEquals(date('Y-m-d', $target), date('Y-m-d', $last['start']));
    }

    public function testEventCreationYearlyDate(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals('09-15', date('m-d', $last['start']));
    }

    public function testEventCreationYearlyWeekday(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(date('m-d', strtotime('last Friday of June', strtotime('+5 years'))), date('m-d', $last['start']));
    }

    public function testEventComparison(): void
    {
        foreach ($this->recIds as $type => $id) {
            $rec = new CalRecurrence($id);
            $rec->createEvents();
            $events = $this->getEventsByRecurrence($rec->getId());
            foreach ($events as $i => $evt) {
                $result = $rec->compareFieldsOfEvent($evt, $rec);
                if ($i == 0) {
                    foreach ($result as $key => $field) {
                        if (str_starts_with($field, '_')) {
                            unset($result[$key]);
                        }
                    }
                }
                $this->assertEquals([], $result);
            }
        }
    }

    public function testEventDiffComparisonWeekly(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setWeekdays('MO');
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_weekdays'], $result);
    }

    public function testEventDiffComparisonMonthly(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setDayOfMonth(20);
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_dayOfMonth'], $result);
    }

    public function testEventDiffComparisonYearly(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setYearlyWeekdayValue('3WE');
        $rec->setYearlyWeekMonth(1);
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_yearlyWeekdayValue', '_yearlyWeekMonth'], $result);
    }

    public static function seriesUpdateModes(): array
    {
        return [
            'update every unchanged event' => [false],
            'update every event' => [true],
        ];
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testDeletedOccurrenceIsNotRecreatedBySeriesEdits(bool $updateManuallyChangedEvents): void
    {
        $calendarlib = TikiLib::lib('calendar');
        $unrelatedEvents = $this->createUnrelatedSeries('weekly');
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $deletedStart = $events[2]['start'];
        // moved before being deleted: the original slot is the one to remember
        $calendarlib->move_item($events[3]['calitemId'], 3600);
        $calendarlib->drop_item('admin', $events[2]['calitemId']);
        $calendarlib->drop_item('admin', $events[3]['calitemId']);

        $rec->setName('Renamed daily event');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));
        $this->assertCount(3, $this->getEventsByRecurrence($rec->getId()));

        // a time change moves the slots, the deleted ones follow and stay deleted on the next edits too
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->setStart('1400');
        $rec->setEnd('1500');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->setName('Daily event');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));

        $events = $this->getEventsByRecurrence($rec->getId());
        $this->assertCount(3, $events);
        $this->assertNotContains($deletedStart + 2 * 3600, array_column($events, 'start'));
        $this->assertEquals($unrelatedEvents, $this->getEventsByRecurrence($this->recIds['weekly']));
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testDeletedOccurrenceStaysDeletedWhenSeriesStartMovesBackAndForth(bool $updateManuallyChangedEvents): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $startPeriod = $rec->getStartPeriod();
        TikiLib::lib('calendar')->drop_item('admin', $events[4]['calitemId']);

        // the last slot falls outside the shortened series, then comes back
        foreach ([$startPeriod + 86400, $startPeriod] as $newStartPeriod) {
            $rec = new CalRecurrence($this->recIds['daily']);
            $rec->setStartPeriod($newStartPeriod);
            $this->assertTrue($rec->save($updateManuallyChangedEvents));
        }

        $after = $this->getEventsByRecurrence($rec->getId());
        $this->assertCount(4, $after);
        $this->assertNotContains($events[4]['start'], array_column($after, 'start'));
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testMovedOccurrenceIsNotDuplicatedBySeriesEdit(bool $updateManuallyChangedEvents): void
    {
        $unrelatedEvents = $this->createUnrelatedSeries('daily');
        $rec = new CalRecurrence($this->recIds['weekly']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $moved = $events[1];
        // Wednesday to Thursday, as dragged in the calendar
        TikiLib::lib('calendar')->move_item($moved['calitemId'], 86400);

        $rec->setName('Renamed weekly event');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));

        $after = $this->getEventsByRecurrence($rec->getId());
        $this->assertCount(count($events), $after);
        $this->assertNotContains($moved['start'], array_column($after, 'start'));
        $item = $this->findEvent($after, $moved['calitemId']);
        $this->assertEquals($moved['start'] + 86400, $item['start']);
        $this->assertEquals($updateManuallyChangedEvents ? 'Renamed weekly event' : 'Bi-weekly wednesday event', $item['name']);
        $this->assertEquals($unrelatedEvents, $this->getEventsByRecurrence($this->recIds['daily']));
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testOccurrenceMovedOntoAnotherSlotIsNotDuplicated(bool $updateManuallyChangedEvents): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        // dragged onto the next day, where the series already has an occurrence
        TikiLib::lib('calendar')->move_item($events[1]['calitemId'], 86400);

        $rec->setName('Renamed daily event');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));

        $after = $this->getEventsByRecurrence($rec->getId());
        $this->assertCount(count($events), $after);
        $this->assertNotContains($events[1]['start'], array_column($after, 'start'));
        $this->assertEquals($events[2]['start'], $this->findEvent($after, $events[1]['calitemId'])['start']);
        $occupant = $this->findEvent($after, $events[2]['calitemId']);
        $this->assertEquals($events[2]['start'], $occupant['start']);
        $this->assertEquals('Renamed daily event', $occupant['name']);
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testAttendeesOfModifiedOccurrenceSurviveSeriesEdit(bool $updateManuallyChangedEvents): void
    {
        $roles = TikiLib::lib('tiki')->table('tiki_calendar_roles', false);
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->setOrganizers(['admin']);
        $rec->setParticipants([['username' => 'admin', 'role' => '1']]);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        TikiLib::lib('calendar')->move_item($events[1]['calitemId'], 3600);

        // reloaded series, as in a CalDAV update that carries no attendees
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->setName('Renamed daily event');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));

        $this->assertEquals(2, $roles->fetchCount(['calitemId' => $events[1]['calitemId']]));
        $this->assertEquals(2, $roles->fetchCount(['calitemId' => $events[0]['calitemId']]));
    }

    /**
     * @dataProvider seriesUpdateModes
     */
    public function testResizedOccurrenceIsTreatedAsModified(bool $updateManuallyChangedEvents): void
    {
        $unrelatedEvents = $this->createUnrelatedSeries('weekly');

        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $resized = $events[1];
        TikiLib::lib('calendar')->resize_item($resized['calitemId'], 1800);
        $this->assertEquals(1, $this->findEvent($this->getEventsByRecurrence($rec->getId()), $resized['calitemId'])['changed']);

        $rec->setEnd('1400');
        $this->assertTrue($rec->save($updateManuallyChangedEvents));

        $after = $this->getEventsByRecurrence($rec->getId());
        $expectedEnd = $updateManuallyChangedEvents ? $resized['start'] + 2 * 3600 : $resized['end'] + 1800;
        $this->assertEquals($expectedEnd, $this->findEvent($after, $resized['calitemId'])['end']);
        $this->assertEquals($events[0]['start'] + 2 * 3600, $this->findEvent($after, $events[0]['calitemId'])['end']);
        $this->assertEquals($unrelatedEvents, $this->getEventsByRecurrence($this->recIds['weekly']));
    }

    private function createUnrelatedSeries(string $type): array
    {
        $unrelated = new CalRecurrence($this->recIds[$type]);
        $unrelated->createEvents();
        return $this->getEventsByRecurrence($unrelated->getId());
    }

    private function findEvent(array $events, $calitemId): array
    {
        foreach ($events as $event) {
            if ($event['calitemId'] == $calitemId) {
                return $event;
            }
        }
        $this->fail("Event $calitemId not found");
    }

    private function getEventsByRecurrence($id)
    {
        $query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
                 . "user, created, lastModif, changed, recurrenceStart "
                 . "FROM tiki_calendar_items WHERE recurrenceId = ? ORDER BY start";
        $bindvars = [(int)$id];
        return TikiLib::lib('calendar')->fetchAll($query, $bindvars);
    }
}
