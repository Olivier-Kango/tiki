<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Tests\Scheduler;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Scheduler_Item;
use Tiki_Log;
use TikiLib;

/**
 * Class ItemTest
 */
class ItemTest extends TestCase
{

    protected static $items = [];

    public static function tearDownAfterClass(): void
    {
        $schedlib = TikiLib::lib('scheduler');

        foreach (self::$items as $itemId) {
            $schedlib->remove_scheduler($itemId);
        }
    }

    /**
     * @covers Scheduler_Item::isStalled()
     */
    public function testIsStalled()
    {
        global $prefs;

        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"index:rebuild"}',
            'run_time' => '*/10 * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 0,
            'user_run_now' => null,
        ], $logger);

        $scheduler->save();

        self::$items[] = $scheduler->id;

        $schedlib = TikiLib::lib('scheduler');

        // Test just start running scheduler
        $schedlib->start_scheduler_run($scheduler->id);
        $this->assertFalse($scheduler->isStalled(false));

        // Test over threshold running scheduler
        $threshold = $prefs['scheduler_stalled_timeout'] = 15;
        $startTime = strtotime(sprintf('-%d min', $threshold));

        $schedlib->start_scheduler_run($scheduler->id, $startTime);

        $this->assertNotFalse($scheduler->isStalled(false));

        $lastRun = $scheduler->getLastRun();
        $this->assertEquals('running', $lastRun['status']);
        $this->assertEmpty($lastRun['end_time']);
        $this->assertTrue((bool) $lastRun['stalled']);

        // Test running scheduler with disabled 'stalled'
        $prefs['scheduler_stalled_timeout'] = 0;
        $startTime = strtotime(sprintf('-%d min', $threshold));

        $schedlib->start_scheduler_run($scheduler->id, $startTime);
        $this->assertFalse($scheduler->isStalled(false));

        $lastRun = $scheduler->getLastRun();
        $this->assertEquals('running', $lastRun['status']);
        $this->assertEmpty($lastRun['end_time']);
        $this->assertFalse((bool) $lastRun['stalled']);
    }

    public function testReduceLogs()
    {
        global $prefs;

        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"index:rebuild"}',
            'run_time' => '*/10 * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 0,
            'user_run_now' => null,
        ], $logger);


        $scheduler->save();
        self::$items[] = $scheduler->id;

        $schedlib = TikiLib::lib('scheduler');

        $totalRuns = 100;
        for ($i = 0; $i < $totalRuns; $i++) {
            // Simulate runs
            $schedlib->start_scheduler_run($scheduler->id);
        }

        $this->assertEquals($totalRuns, $schedlib->countRuns($scheduler->id));

        $scheduler->reduceLogs(0);
        $this->assertEquals($totalRuns, $schedlib->countRuns($scheduler->id));

        $scheduler->reduceLogs(50); // Keep last 50 logs
        $this->assertEquals(50, $schedlib->countRuns($scheduler->id));

        $expect = $prefs['scheduler_keep_logs'] = 10;
        $scheduler->reduceLogs(); // Reduce logs to tiki preferences
        $this->assertEquals($expect, $schedlib->countRuns($scheduler->id));
    }

    /**
     * Tests run_only_once scheduler status
     * @throws Exception
     */
    public function testRunOnlyOnce()
    {
        global $prefs, $tikilib;
        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ShellCommandTask',
            'params' => '{"shell_command":"php -v","timeout":""}',
            'run_time' => '* * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 1,
            'user_run_now' => null,
        ], $logger);

        $scheduler->save();
        self::$items[] = $scheduler->id;

        $schedlib = TikiLib::lib('scheduler');

        // Run scheduler
        $scheduler->execute();
        $lastRun = $scheduler->getLastRun();

        // Assert that run has been finished
        $this->assertEquals('done', $lastRun['status']);

        // Get scheduler with updated information. It should be inactive as it should only run once
        $scheduler = $schedlib->get_scheduler($scheduler->id);
        $this->assertEquals(Scheduler_Item::STATUS_INACTIVE, $scheduler['status']);
    }

    /**
     * @covers Scheduler_Item::heal()
     */
    public function testHeal()
    {
        global $prefs, $tikilib;

        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"index:rebuild"}',
            'run_time' => '*/10 * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 0,
            'user_run_now' => null,
        ], $logger);

        $scheduler->save();
        self::$items[] = $scheduler->id;

        $schedlib = TikiLib::lib('scheduler');
        $message = 'Heal Unit Test';

        // Running scheduler since now
        $schedlib->start_scheduler_run($scheduler->id);
        $this->assertFalse($scheduler->heal($message, false));

        // Running scheduler since now
        $threshold = $tikilib->get_preference('scheduler_healing_timeout', 30);
        $schedlib->start_scheduler_run($scheduler->id, strtotime(sprintf("-%d min", $threshold)));
        $this->assertTrue($scheduler->heal($message, false));

        $lastRun = $scheduler->getLastRun();
        $this->assertEquals('failed', $lastRun['status']);
        $this->assertNotEmpty($lastRun['end_time']);
        $this->assertEquals('Heal Unit Test', $lastRun['output']);
        $this->assertTrue((bool)$lastRun['healed']);

        // With Self healing disabled
        $prefs['scheduler_healing_timeout'] = 0;
        $schedlib->start_scheduler_run($scheduler->id, strtotime(sprintf("-%d min", $threshold)));
        $this->assertFalse($scheduler->heal($message, false));

        $lastRun = $scheduler->getLastRun();
        $this->assertEquals('running', $lastRun['status']);
        $this->assertEmpty($lastRun['end_time']);
        $this->assertFalse((bool)$lastRun['healed']);
    }

    public function testGetPreviousRunDateWithNoDelay()
    {
        $schedulerStub = $this->createPartialMock(Scheduler_Item::class, []);
        $schedulerStub->user_run_now = 1;
        $schedulerStub->run_time = '0 * * * *'; // Every hour

        $time = time();
        $expectedTime = $time - ($time % 3600); // We want the hour on 0 minutes
        $runDate = $schedulerStub->getPreviousRunDate();

        $this->assertEquals($expectedTime, $runDate);
    }

    public function testGetPreviousRunDateWithDelay()
    {
        global $prefs;
        $delay = 30; // 30 min delay
        $prefs['scheduler_delay'] = $delay;

        $schedulerStub = $this->createPartialMock(Scheduler_Item::class, []);
        $schedulerStub->user_run_now = 1;
        $schedulerStub->run_time = '0 * * * *'; // Every hour
        $time = strtotime($delay . ' minutes ago');
        $expectedTime = $time - ($time % 3600) + ($delay * 60); // We want the hour on 0 minutes
        $runDate = $schedulerStub->getPreviousRunDate();

        $this->assertEquals($expectedTime, $runDate);
        $this->assertLessThan(time(), $runDate);
    }
}
