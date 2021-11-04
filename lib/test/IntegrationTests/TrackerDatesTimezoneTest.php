<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
/**
 * @group integration
 * The purpose of these tests is to ensure consistency between storage, display
 * and filtering of tracker date/time fields. All TODO items below are actually
 * false-positives in order for tests to pass. They show the places where Tiki
 * needs fixing in order to return the right results.
 */
class TrackerDatesTimezoneTest extends TikiTestCase
{
    protected static $trklib;
    protected static $unifiedlib;
    protected static $trackerId;
    protected static $old_prefs;
    protected static $old_tz;
    protected static $ist = 'Asia/Kolkata'; // GMT+5:30
    protected static $est = 'America/New_York'; // GMT-5:00

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        self::$old_prefs = $prefs;
        self::$old_tz = date_default_timezone_get();
        $prefs['feature_trackers'] = 'y';
        $prefs['short_date_format'] = '%Y-%m-%d';
        $prefs['short_time_format'] = '%H:%M';

        parent::setUpBeforeClass();
        self::$trklib = TikiLib::lib('trk');

        // create tracker and couple of fields
        self::$trackerId = self::$trklib->replace_tracker(null, 'Test Tracker', '', [], 'n');

        $fields = [[
            'name' => 'Date (legacy)',
            'type' => 'f',
            'isHidden' => 'n',
            'isMandatory' => 'n',
            'permName' => 'test_date_legacy',
            'options' => json_encode(['datetime' => 'd']),
        ], [
            'name' => 'DateTime (legacy)',
            'type' => 'f',
            'isHidden' => 'n',
            'isMandatory' => 'n',
            'permName' => 'test_datetime_legacy',
            'options' => json_encode(['datetime' => 'dt']),
        ], [
            'name' => 'Date',
            'type' => 'j',
            'isHidden' => 'n',
            'isMandatory' => 'n',
            'permName' => 'test_date',
            'options' => json_encode(['datetime' => 'd']),
        ], [
            'name' => 'DateTime',
            'type' => 'j',
            'isHidden' => 'n',
            'isMandatory' => 'n',
            'permName' => 'test_datetime',
            'options' => json_encode(['datetime' => 'dt']),
        ]];
        foreach ($fields as $i => $field) {
            self::$trklib->replace_tracker_field(
                self::$trackerId,
                0,
                $field['name'],
                $field['type'],
                'y',
                'y',
                'y',
                'y',
                $field['isHidden'],
                $field['isMandatory'],
                ($i + 1) * 10,
                $field['options'] ?? '',
                '',
                '',
                null,
                '',
                null,
                null,
                'n',
                '',
                '',
                '',
                $field['permName']
            );
        }

        TikiDb::get()->query("REPLACE INTO `users_grouppermissions` VALUES('Registered', 'tiki_p_admin_trackers', '')");
        TikiDb::get()->query("REPLACE INTO `users_grouppermissions` VALUES('Registered', 'tiki_p_view_trackers', '')");
        $builder = new Perms_Builder();
        Perms::set($builder->build());

        // impersonate a regitered user
        new Perms_Context('someone');
        $perms = Perms::getInstance();
        $perms->setGroups(['Registered']);

        self::$unifiedlib = TikiLib::lib('unifiedsearch');
        self::$unifiedlib->rebuild();
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;

        if (! empty($prefs['unified_mysql_index_current'])) {
            TikiDb::get()->query("DROP TABLE `{$prefs['unified_mysql_index_current']}`");
        }

        $prefs = self::$old_prefs;
        date_default_timezone_set(self::$old_tz);

        parent::tearDownAfterClass();
        self::$trklib->remove_tracker(self::$trackerId);

        TikiDb::get()->query("DELETE FROM `users_grouppermissions` WHERE `groupName` = 'Registered' AND `permName` = 'tiki_p_admin_trackers'");
        TikiDb::get()->query("DELETE FROM `users_grouppermissions` WHERE `groupName` = 'Registered' AND `permName` = 'tiki_p_view_trackers'");
        $builder = new Perms_Builder();
        Perms::set($builder->build());
    }

    public function testTimeStorageInUTC(): void
    {
        global $prefs;

        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = 'UTC';

        $this->executeTest([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00',
            'test_date' => '2021-06-01',
            'test_datetime' => '2021-06-01 10:00',
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => '2021-06-01',
            'test_datetime' => '2021-06-01 10:00:00',
        ]);
    }

    public function testTimeStorageServerNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = 'UTC';

        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->executeTest([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 04:30',
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 04:30:00',
        ]);
    }

    public function testTimeStorageServerTikiSameNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$ist;

        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->executeTest([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00',
            'test_date' => '2021-06-01',
            'test_datetime' => '2021-06-01 10:00',
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 04:30:00',
            'test_date' => '2021-06-01',
            'test_datetime' => '2021-06-01 04:30:00',
        ]);
    }

    public function testTimeStorageServerTikiDiffNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$est;

        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->executeTest([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 00:30',
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 14:00:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 04:30:00',
        ]);
    }

    public function testTimeStorageInUTCWithBrowserOffset(): void
    {
        global $prefs;

        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = 'UTC';

        $this->executeTest(
            [
                'test_date_legacy' => '2021-06-01',
                'test_datetime_legacy' => '2021-06-01 10:00:00',
                'test_date' => strtotime('2021-06-01') - 180 * 60,
                'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
            ],
            [
                'test_date_legacy' => '2021-06-01',
                'test_datetime_legacy' => '2021-06-01 10:00',
                'test_date' => '2021-06-01',
                'test_datetime' => '2021-06-01 10:00',
            ],
            [
                'test_date_legacy' => '2021-06-01',
                'test_datetime_legacy' => '2021-06-01 10:00:00',
                'test_date' => '2021-06-01',
                'test_datetime' => '2021-06-01 10:00:00',
            ],
            -180
        );
    }

    public function testTimeStorageServerTikiDiffNonUTCWithBrowserOffset(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$est;

        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->executeTest([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01') - 180 * 60,
            'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 04:30',
        ], [
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 14:00:00',
            'test_date' => '2021-05-31',
            'test_datetime' => '2021-06-01 08:30:00',
        ], -180);
    }

    public function testStoreRetrieveDiffTimezonesDB(): void
    {
        global $prefs;
        date_default_timezone_set(self::$est);
        $prefs['display_timezone'] = self::$ist;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01') - 180 * 60,
            'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
        ], -180);

        // TODO: fix Tiki - retrieving in an western timezone shifts the date
        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = self::$est;

        $values = $this->getItemValues($itemId);
        $this->assertEquals('2021-05-31', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 00:30', $values['test_datetime_legacy']);
        $this->assertEquals('2021-05-31', $values['test_date']);
        $this->assertEquals('2021-06-01 04:30', $values['test_datetime']);
    }

    public function testStoreRetrieveDiffTimezonesIndex(): void
    {
        global $prefs;
        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$ist;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01') - 180 * 60,
            'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
        ], -180);

        require_once('lib/search/refresh-functions.php');
        refresh_index('trackeritem', $itemId);

        // TODO: fix Tiki - retrieving in an western timezone shifts the date
        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = self::$est;

        $query = self::$unifiedlib->buildQuery([
            'type' => 'trackeritem',
            'object_id' => $itemId,
        ]);
        $result = $query->search(self::$unifiedlib->getIndex());
        $resultArray = $result->getArrayCopy();

        $this->assertEquals('2021-06-01', $resultArray[0]['tracker_field_test_date_legacy']);
        $this->assertEquals('2021-06-01 04:30:00', $resultArray[0]['tracker_field_test_datetime_legacy']);
        $this->assertEquals('2021-05-31', $resultArray[0]['tracker_field_test_date']);
        $this->assertEquals('2021-05-31 23:00:00', $resultArray[0]['tracker_field_test_datetime']);
    }

    public function testQueryFilters(): void
    {
        global $prefs;
        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$ist;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01') - 180 * 60,
            'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
        ], -180);

        require_once('lib/search/refresh-functions.php');
        refresh_index('trackeritem', $itemId);

        $results = [
            'test_date_legacy' => 1,
            'test_datetime_legacy' => 1,
            'test_date' => 0, // TODO: fix Tiki as it should find the date
            'test_datetime' => 1,
        ];

        foreach ($results as $field => $expectedCount) {
            $query = self::$unifiedlib->buildQuery([
                'type' => 'trackeritem',
                'object_id' => $itemId,
            ]);

            $subquery = new Search_Query(null, 'or');

            $filter = Tracker\Filter\Collection::getFilter('test_date', 'range', true);
            $input = new JitFilter([
                'tf_' . $field . '_range_from' => strtotime('2021-06-01') - 180 * 60,
                'tf_' . $field . '_range_to' => strtotime('2021-06-01') - 180 * 60,
                'tzoffset' => -180,
            ]);
            $filter->applyInput($input);
            $filter->applyCondition($subquery);

            $query->getExpr()->addPart($subquery->getExpr());

            $result = $query->search(self::$unifiedlib->getIndex());
            $resultArray = $result->getArrayCopy();

            $this->assertCount($expectedCount, $resultArray, "Field: $field");
        }
    }

    public function testTrackerBuildDate(): void
    {
        global $prefs;
        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = self::$ist;

        $ins_id = "ins_123";
        $input = [
            $ins_id . 'Year' => '2021',
            $ins_id . 'Month' => '6',
            $ins_id . 'Day' => '1',
            $ins_id . 'Hour' => '10',
            $ins_id . 'Minute' => '00',
        ];

        $timestamp = self::$trklib->build_date($input, 'dt', $ins_id);
        date_default_timezone_set($prefs['display_timezone']);
        $this->assertEquals('2021-06-01 10:00:00', date('Y-m-d H:i:s', $timestamp));
    }

    public function testTrackerFilter(): void
    {
        global $prefs;
        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$ist;

        $utilities = new Services_Tracker_Utilities();
        $utilities->clearTracker(self::$trackerId);

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01') - 180 * 60,
            'test_datetime' => strtotime('2021-06-01 10:00:00') - 180 * 60,
        ], -180);

        $ins_id = "ins_123";
        $input = [
            $ins_id . 'Year' => '2021',
            $ins_id . 'Month' => '6',
            $ins_id . 'Day' => '1',
            $ins_id . 'Hour' => '10',
            $ins_id . 'Minute' => '00',
        ];

        $timestamps = [
            'test_date_legacy' => self::$trklib->build_date($input, 'd', $ins_id),
            'test_datetime_legacy' => self::$trklib->build_date($input, 'dt', $ins_id),
            'test_date' => self::$trklib->build_date($input, 'd', $ins_id),
            'test_datetime' => self::$trklib->build_date($input, 'dt', $ins_id),
        ];

        $results = [
            'test_date_legacy' => 1,
            'test_datetime_legacy' => 1,
            'test_date' => 0, // TODO: fix Tiki as it should find the date
            'test_datetime' => 0,
        ];

        foreach ($timestamps as $permName => $timestamp) {
            $definition = Tracker_Definition::get(self::$trackerId);
            $field = $definition->getFieldFromPermName($permName);

            $items = self::$trklib->list_items(
                self::$trackerId,
                0,
                -1,
                '',
                '',
                [$field['fieldId']],
                '',
                '',
                '',
                [$timestamp]
            );
            $this->assertCount($results[$permName], $items['data'], "Field: $permName");
        }
    }

    private function executeTest($input, $output, $index, $tzoffset = null)
    {
        $itemId = $this->createItem($input, $tzoffset);
        $values = $this->getItemValues($itemId);
        $this->assertEquals($output['test_date_legacy'], $values['test_date_legacy']);
        $this->assertEquals($output['test_datetime_legacy'], $values['test_datetime_legacy']);
        $this->assertEquals($output['test_date'], $values['test_date']);
        $this->assertEquals($output['test_datetime'], $values['test_datetime']);

        require_once('lib/search/refresh-functions.php');
        refresh_index('trackeritem', $itemId);

        $query = self::$unifiedlib->buildQuery([
            'type' => 'trackeritem',
            'object_id' => $itemId,
        ]);
        $result = $query->search(self::$unifiedlib->getIndex());
        $resultArray = $result->getArrayCopy();

        $this->assertEquals($index['test_date_legacy'], $resultArray[0]['tracker_field_test_date_legacy']);
        $this->assertEquals($index['test_datetime_legacy'], $resultArray[0]['tracker_field_test_datetime_legacy']);
        $this->assertEquals($index['test_date'], $resultArray[0]['tracker_field_test_date']);
        $this->assertEquals($index['test_datetime'], $resultArray[0]['tracker_field_test_datetime']);
    }

    private function createItem($fieldValues, $tzoffset = null)
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();
        $input = ['fields' => $fieldValues];
        if (! empty($fieldValues['test_date_legacy'])) {
            $date = explode('-', $fieldValues['test_date_legacy']);
            $input['ins_' . $fields[0]['fieldId'] . 'Year'] = intval($date[0]);
            $input['ins_' . $fields[0]['fieldId'] . 'Month'] = intval($date[1]);
            $input['ins_' . $fields[0]['fieldId'] . 'Day'] = intval($date[2]);
        }
        if (! empty($fieldValues['test_datetime_legacy'])) {
            list($date, $time) = explode(' ', $fieldValues['test_datetime_legacy']);
            $date = explode('-', $date);
            $time = explode(':', $time);
            $input['ins_' . $fields[1]['fieldId'] . 'Year'] = intval($date[0]);
            $input['ins_' . $fields[1]['fieldId'] . 'Month'] = intval($date[1]);
            $input['ins_' . $fields[1]['fieldId'] . 'Day'] = intval($date[2]);
            $input['ins_' . $fields[1]['fieldId'] . 'Hour'] = intval($time[0]);
            $input['ins_' . $fields[1]['fieldId'] . 'Minute'] = $time[1];
        }
        if (! empty($tzoffset)) {
            $input['tzoffset'] = $tzoffset;
        }
        $itemObject = Tracker_Item::newItem(self::$trackerId);
        $processedFields = $itemObject->prepareInput(new JitFilter($input));
        foreach ($processedFields as $k => $f) {
            $fields[$k]['value'] = isset($f['value']) ? $f['value'] : '';
        }
        return self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
    }

    private function getItemValues($itemId)
    {
        $result = [];
        $definition = Tracker_Definition::get(self::$trackerId);
        foreach (['test_date_legacy', 'test_datetime_legacy', 'test_date', 'test_datetime'] as $permName) {
            $field = $definition->getFieldFromPermName($permName);
            $result[$permName] = self::$trklib->field_render_value(
                [
                    'field' => $field,
                    'process' => 'y',
                    'list_mode' => 'csv',
                    'itemId' => $itemId,
                ]
            );
        }
        return $result;
    }
}
