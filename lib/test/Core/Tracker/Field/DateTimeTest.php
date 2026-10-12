<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

class TrackerFieldDateTimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fieldTypeProvider
     */
    public function testRenderDiffAcceptsFormattedOldValue(string $type): void
    {
        \Feedback::clear();
        $handler = $this->createHandler($type, '1790467200');

        $diff = $handler->renderDiff([
            'oldValue' => '26/09/2026 19:20',
            'value' => '1790467200',
            'history' => 'y',
            'diff_style' => 'sidediff',
        ]);

        $this->assertStringContainsString('26/09/2026 19:20', strip_tags($diff));
        $this->assertEmpty(\Feedback::errorMessages());
    }

    public static function fieldTypeProvider(): array
    {
        return [
            'Date and Time' => ['f'],
            'Date and Time (Date Picker)' => ['j'],
        ];
    }

    private function createHandler(string $type, string $value): \Tracker_Field_DateTime
    {
        $fieldInfo = [
            'fieldId' => 1,
            'trackerId' => 1,
            'type' => $type,
            'name' => 'Date',
            'permName' => 'date',
            'options' => json_encode(['datetime' => 'd']),
            'options_map' => ['datetime' => 'd'],
            'value' => $value,
        ];
        $definition = \Tracker_Definition::createFake(['trackerId' => 1], [$fieldInfo]);
        $class = $type === 'j' ? \Tracker_Field_JsCalendar::class : \Tracker_Field_DateTime::class;

        return new $class($fieldInfo, ['itemId' => 1, 1 => $value], $definition);
    }
}
