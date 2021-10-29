<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
/**
 * @group integration
 */
class TrackerDatesTimezoneTest extends TikiTestCase
{
    protected static $trklib;
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

        TikiDb::get()->query("INSERT INTO `users_grouppermissions` VALUES('Registered', 'tiki_p_admin_trackers', '')");
        $builder = new Perms_Builder();
        Perms::set($builder->build());

        // impersonate a regitered user
        new Perms_Context('someone');
        $perms = Perms::getInstance();
        $perms->setGroups(['Registered']);
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;
        $prefs = self::$old_prefs;
        date_default_timezone_set(self::$old_tz);

        parent::tearDownAfterClass();
        self::$trklib->remove_tracker(self::$trackerId);

        TikiDb::get()->query("DELETE FROM `users_grouppermissions` WHERE `groupName` = 'Registered' AND `permName` = 'tiki_p_admin_trackers'");
        $builder = new Perms_Builder();
        Perms::set($builder->build());
    }

    public function testTimeStorageInUTC(): void
    {
        global $prefs;

        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = 'UTC';

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ]);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        $this->assertEquals('2021-06-01', $values['test_date']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime']);
    }

    public function testTimeStorageServerNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = 'UTC';

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ]);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->assertEquals('2021-05-31', $values['test_date']);
        $this->assertEquals('2021-06-01 04:30', $values['test_datetime']);
    }

    public function testTimeStorageServerTikiSameNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$ist;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ]);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        $this->assertEquals('2021-06-01', $values['test_date']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime']);
    }

    public function testTimeStorageServerTikiDiffNonUTC(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$est;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01'),
            'test_datetime' => strtotime('2021-06-01 10:00:00'),
        ]);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->assertEquals('2021-05-31', $values['test_date']);
        $this->assertEquals('2021-06-01 00:30', $values['test_datetime']);
    }

    public function testTimeStorageInUTCWithBrowserOffset(): void
    {
        global $prefs;

        date_default_timezone_set('UTC');
        $prefs['display_timezone'] = 'UTC';

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01')-180*60,
            'test_datetime' => strtotime('2021-06-01 10:00:00')-180*60,
        ],
        -180);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        $this->assertEquals('2021-06-01', $values['test_date']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime']);
    }

    public function testTimeStorageServerTikiDiffNonUTCWithBrowserOffset(): void
    {
        global $prefs;

        date_default_timezone_set(self::$ist);
        $prefs['display_timezone'] = self::$est;

        $itemId = $this->createItem([
            'test_date_legacy' => '2021-06-01',
            'test_datetime_legacy' => '2021-06-01 10:00:00',
            'test_date' => strtotime('2021-06-01')-180*60,
            'test_datetime' => strtotime('2021-06-01 10:00:00')-180*60,
        ],
        -180);

        $values = $this->getItemValues($itemId);

        $this->assertEquals('2021-06-01', $values['test_date_legacy']);
        $this->assertEquals('2021-06-01 10:00', $values['test_datetime_legacy']);
        // TODO: fix Tiki - the following values are not identical to what user entered
        $this->assertEquals('2021-05-31', $values['test_date']);
        $this->assertEquals('2021-06-01 04:30', $values['test_datetime']);
    }

    private function createItem($fieldValues, $tzoffset = null) {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();
        $input = ['fields' => $fieldValues];
        if (!empty($fieldValues['test_date_legacy'])) {
            $date = explode('-', $fieldValues['test_date_legacy']);
            $input['ins_'.$fields[0]['fieldId'].'Year'] = intval($date[0]);
            $input['ins_'.$fields[0]['fieldId'].'Month'] = intval($date[1]);
            $input['ins_'.$fields[0]['fieldId'].'Day'] = intval($date[2]);
        }
        if (!empty($fieldValues['test_datetime_legacy'])) {
            list($date, $time) = explode(' ', $fieldValues['test_datetime_legacy']);
            $date = explode('-', $date);
            $time = explode(':', $time);
            $input['ins_'.$fields[1]['fieldId'].'Year'] = intval($date[0]);
            $input['ins_'.$fields[1]['fieldId'].'Month'] = intval($date[1]);
            $input['ins_'.$fields[1]['fieldId'].'Day'] = intval($date[2]);
            $input['ins_'.$fields[1]['fieldId'].'Hour'] = intval($time[0]);
            $input['ins_'.$fields[1]['fieldId'].'Minute'] = $time[1];
        }
        if (!empty($tzoffset)) {
            $input['tzoffset'] = $tzoffset;
        }
        $itemObject = Tracker_Item::newItem(self::$trackerId);
        $processedFields = $itemObject->prepareInput(new JitFilter($input));
        foreach ($processedFields as $k => $f) {
            $fields[$k]['value'] = isset($f['value']) ? $f['value'] : '';
        }
        return self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
    }

    private function getItemValues($itemId) {
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
