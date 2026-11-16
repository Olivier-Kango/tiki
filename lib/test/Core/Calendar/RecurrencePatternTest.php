<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Calendar;

use CalendarLib;
use CalRecurrence;
use JitFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Services_Calendar_BaseController;
use Services_Exception;
use TikiDb;
use TikiDb_Transaction;
use TikiLib;
use TikiTestCase;

require_once __DIR__ . '/../../../tikilib.php';
require_once __DIR__ . '/../../../calendar/calendarlib.php';

class RecurrencePatternTest extends TikiTestCase
{
    private array $previousLibraries;

    protected function setUp(): void
    {
        $libraries = new ReflectionProperty(TikiLib::class, 'libraries');
        $this->previousLibraries = $libraries->getValue();
        $calendar = $this->createMock(CalendarLib::class);
        $calendar->method('list_locations')->willReturn([]);
        $calendar->method('list_categories')->willReturn([]);
        $libraries->setValue(null, array_merge($this->previousLibraries, ['calendar' => $calendar]));
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(TikiLib::class, 'libraries'))->setValue(null, $this->previousLibraries);
    }

    private function build(array $pattern): CalRecurrence
    {
        $controller = new class extends Services_Calendar_BaseController {
            public function build(array $input): CalRecurrence
            {
                return $this->createRecurrenceFromInput(new JitFilter($input));
            }
        };
        $recurrence = $controller->build(array_merge([
            'startPeriod' => strtotime('2027-06-25 UTC'),
            'endType' => 'nb',
            'nbRecurrences' => 3,
        ], $pattern));
        $recurrence->setCalendarId(1);
        $recurrence->setName('Pattern regression');
        $recurrence->setUid('calendar-pattern-test');
        $recurrence->setLang('en');
        $recurrence->setStart(900);
        $recurrence->setEnd(1000);
        $recurrence->setDuration(3600);
        $recurrence->setRecurenceDstTimezone('UTC');
        return $recurrence;
    }

    public static function patterns(): array
    {
        return [
            'daily' => [
                ['recurrenceType' => 'daily', 'days' => 1],
                ['2027-06-25', '2027-06-26', '2027-06-27'],
            ],
            'weekly' => [
                ['recurrenceType' => 'weekly', 'weeks' => 1, 'weekdays' => ['FR']],
                ['2027-06-25', '2027-07-02', '2027-07-09'],
            ],
            'monthly date' => [
                [
                    'recurrenceType' => 'monthly', 'months' => 1,
                    'recurrenceTypeMonthy' => 'date', 'dayOfMonth' => [25],
                ],
                ['2027-06-25', '2027-07-25', '2027-08-25'],
            ],
            'legacy stored monthly date' => [
                [
                    'recurrenceType' => 'monthly', 'months' => 1,
                    'recurrenceTypeMonthy' => 'date', 'dayOfMonth' => [25],
                ],
                ['2027-06-25', '2027-07-25', '2027-08-25'],
                'monthlyType',
            ],
            'monthly weekday' => [
                [
                    'recurrenceType' => 'monthly', 'months' => 1,
                    'recurrenceTypeMonthy' => 'weekday', 'monthlyWeekNumber' => -1, 'monthlyWeekday' => 'FR',
                ],
                ['2027-06-25', '2027-07-30', '2027-08-27'],
            ],
            'monthly workday' => [
                [
                    'recurrenceType' => 'monthly', 'months' => 1,
                    'recurrenceTypeMonthy' => 'firstlastweekday', 'monthlyFirstLastWeekNumber' => -1,
                    'startPeriod' => strtotime('2027-05-31 UTC'),
                ],
                ['2027-05-31', '2027-06-30', '2027-07-30'],
            ],
            'yearly date' => [
                [
                    'recurrenceType' => 'yearly', 'years' => 1,
                    'recurrenceTypeYearly' => 'date', 'yearlyDay' => 25, 'yearlyMonth' => 6,
                ],
                ['2027-06-25', '2028-06-25', '2029-06-25'],
            ],
            'legacy stored yearly date' => [
                [
                    'recurrenceType' => 'yearly', 'years' => 1,
                    'recurrenceTypeYearly' => 'date', 'yearlyDay' => 25, 'yearlyMonth' => 6,
                ],
                ['2027-06-25', '2028-06-25', '2029-06-25'],
                'yearlyType',
            ],
            'last Friday of June' => [
                [
                    'recurrenceType' => 'yearly', 'years' => 1,
                    'recurrenceTypeYearly' => 'weekday', 'yearlyWeekNumber' => -1, 'yearlyWeekday' => 'FR',
                    // The unused workday-pattern month must not overwrite June.
                    'yearlyWeekMonth' => 6, 'yearlyFirstLastWeekMonth' => 1,
                ],
                ['2027-06-25', '2028-06-30', '2029-06-29'],
            ],
            'yearly first workday' => [
                [
                    'recurrenceType' => 'yearly', 'years' => 1,
                    'recurrenceTypeYearly' => 'firstlastweekday', 'yearlyFirstLastWeekNumber' => 1,
                    'yearlyWeekMonth' => 1, 'yearlyFirstLastWeekMonth' => 6,
                    'startPeriod' => strtotime('2027-06-01 UTC'),
                ],
                ['2027-06-01', '2028-06-01', '2029-06-01'],
            ],
            'yearly last workday' => [
                [
                    'recurrenceType' => 'yearly', 'years' => 1,
                    'recurrenceTypeYearly' => 'firstlastweekday', 'yearlyFirstLastWeekNumber' => -1,
                    'yearlyWeekMonth' => 1, 'yearlyFirstLastWeekMonth' => 6,
                    'startPeriod' => strtotime('2027-06-30 UTC'),
                ],
                ['2027-06-30', '2028-06-30', '2029-06-29'],
            ],
        ];
    }

    #[DataProvider('patterns')]
    public function testPatternSurvivesSaveAndReload(array $input, array $expectedDates, ?string $legacyPatternColumn = null): void
    {
        $recurrence = $this->build($input);
        $database = $this->createMock(TikiDb::class);
        $transaction = $this->getMockBuilder(TikiDb_Transaction::class)->disableOriginalConstructor()->getMock();
        $transaction->expects($this->once())->method('commit');
        $database->expects($this->once())->method('begin')->willReturn($transaction);
        $database->expects($this->once())->method('getOne')->willReturn(42);

        // Capture the actual INSERT bindings; reload exactly what save() wrote.
        $stored = [];
        $database->expects($this->exactly(2))->method('query')->willReturnCallback(
            function ($query, $bindings) use (&$stored, $legacyPatternColumn) {
                if (preg_match('/^INSERT INTO tiki_calendar_recurrence \(([^)]+)\)/', $query, $matches)) {
                    $stored = array_combine(preg_split('/,\s*/', $matches[1]), $bindings);
                    return true;
                }
                $this->assertStringStartsWith('SELECT calendarId,', $query);
                $this->assertSame([42], $bindings);
                if ($legacyPatternColumn !== null) {
                    // Simulate a stored series created before pattern-type columns existed.
                    $stored[$legacyPatternColumn] = null;
                }
                return new class ($stored) {
                    public function __construct(private array $row)
                    {
                    }

                    public function fetchRow(): array
                    {
                        return $this->row;
                    }
                };
            }
        );

        $createdDates = [];
        TikiLib::lib('calendar')->expects($this->exactly(3))->method('set_item')->willReturnCallback(
            function ($user, $id, $event) use (&$createdDates) {
                $this->assertSame(42, $event['recurrenceId']);
                $createdDates[] = gmdate('Y-m-d', $event['start']);
                return count($createdDates);
            }
        );

        $instance = new ReflectionProperty(TikiDb::class, 'instance');
        $previousDatabase = $instance->getValue();
        TikiDb::set($database);
        try {
            $this->assertTrue($recurrence->save());
            $this->assertSame($expectedDates, $createdDates);
            $loaded = new CalRecurrence(42);
            $this->assertTrue($loaded->isValid());
            $expected = $recurrence->toArray();
            $expected['created'] = $stored['created'];
            $expected['lastModif'] = $stored['lastModif'];
            $this->assertEquals($expected, $loaded->toArray());
        } finally {
            $instance->setValue(null, $previousDatabase);
        }
    }

    public static function invalidPatterns(): array
    {
        $cases = [];
        foreach (['monthly' => 'recurrenceTypeMonthy', 'yearly' => 'recurrenceTypeYearly'] as $frequency => $field) {
            foreach ([null, '', 'unknown'] as $pattern) {
                $cases[$frequency . ':' . var_export($pattern, true)] = [$frequency, $field, $pattern];
            }
        }
        return $cases;
    }

    #[DataProvider('invalidPatterns')]
    public function testControllerRejectsInvalidPatternBeforeSerialization(string $frequency, string $field, ?string $pattern): void
    {
        $this->expectException(Services_Exception::class);
        $this->expectExceptionCode(400);
        $this->build([
            'recurrenceType' => $frequency, 'months' => 1, 'years' => 1,
            $field => $pattern, 'dayOfMonth' => [25], 'yearlyMonth' => 6, 'yearlyDay' => 25,
        ]);
    }

    #[DataProvider('invalidPatterns')]
    public function testModelRejectsInvalidPattern(string $frequency, string $field, ?string $pattern): void
    {
        $recurrence = $this->build([
            'recurrenceType' => $frequency, 'months' => 1, 'years' => 1,
            $field => 'date', 'dayOfMonth' => [25], 'yearlyMonth' => 6, 'yearlyDay' => 25,
        ]);
        if ($frequency === 'monthly') {
            $recurrence->setMonthlyType($pattern);
        } else {
            $recurrence->setYearlyType($pattern);
        }
        $this->assertFalse($recurrence->isValid());
        $this->assertFalse($recurrence->save());
    }
}
