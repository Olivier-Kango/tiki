<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Recommendation;

use PHPUnit\Framework\TestCase;

class EngineSetTest extends TestCase
{
    public function testNoEngines()
    {
        $engineSet = new EngineSet();
        $this->assertEquals(0, $engineSet->getCount());
    }

    public function testMultipleEngines()
    {
        $engineSet = new EngineSet();
        $engineSet->register('a', new Engine\FakeEngine([]));
        $engineSet->register('b', new Engine\FakeEngine([]));
        $this->assertEquals(2, $engineSet->getCount());
    }

    public function testMultipleWeightedEngines()
    {
        $engineSet = new EngineSet();
        $engineSet->registerWeighted('a', 1, new Engine\FakeEngine([]));
        $engineSet->registerWeighted('b', 2, new Engine\FakeEngine([]));
        $this->assertEquals(2, $engineSet->getCount());
    }

    public function testDuplicateNames()
    {
        $engineSet = new EngineSet();
        $engineSet->register('a', new Engine\FakeEngine([]));
        $engineSet->register('a', new Engine\FakeEngine([]));
        $this->assertEquals(1, $engineSet->getCount());
    }

    public function testRegisterGenerator()
    {
        $a = new Engine\FakeEngine([]);
        $b = new Engine\FakeEngine([]);

        $out = [
            spl_object_hash($a) => 0,
            spl_object_hash($b) => 0,
        ];

        $engineSet = new EngineSet();
        $engineSet->register('a', $a);
        $engineSet->register('b', $b);

        $engines = $engineSet->getGenerator();
        for ($i = 0; $i < 10; ++$i) {
            [$set, $engine] = $engines->current();
            $engines->next();

            $out[spl_object_hash($engine)]++;
        }

        $this->assertEquals([
            spl_object_hash($a) => 5,
            spl_object_hash($b) => 5,
        ], $out);
    }

    public function testRegisterWeightedGenerator()
    {
        $a = new Engine\FakeEngine([]);
        $b = new Engine\FakeEngine([]);

        $out = [
            spl_object_hash($a) => 0,
            spl_object_hash($b) => 0,
        ];

        $engineSet = new EngineSet();
        $engineSet->registerWeighted('a', 4, $a);
        $engineSet->registerWeighted('b', 1, $b);

        $engines = $engineSet->getGenerator();
        for ($i = 0; $i < 10; ++$i) {
            [$set, $engine] = $engines->current();
            $engines->next();

            $out[spl_object_hash($engine)]++;
        }

        $this->assertEquals([
            spl_object_hash($a) => 8,
            spl_object_hash($b) => 2,
        ], $out);
    }
}
