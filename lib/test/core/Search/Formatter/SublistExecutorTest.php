<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Search\Formatter;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Search\Formatter\Sublist\Executor;
use Search\Formatter\Sublist\Parser;
use Search\Formatter\Sublist\Record;
use Search_Formatter;
use Search_Formatter_Plugin_Interface;

class SublistExecutorTest extends TestCase
{
    public function testEmptyParentContentBecomesExactEmptyMatch()
    {
        $body = $this->rewrite(
            '{filter field="tracker_field_child" content="$parent.tracker_field_parent$"}',
            [['tracker_field_parent' => '']]
        );

        $this->assertStringContainsString('exact=""', $body);
        $this->assertStringNotContainsString('content=', $body);
    }

    public function testEmptyParentContentKeepsSingleQuotes()
    {
        $body = $this->rewrite(
            "{filter field=\"tracker_field_child\" content='\$parent.tracker_field_parent\$'}",
            [['tracker_field_parent' => '']]
        );

        $this->assertStringContainsString("exact=''", $body);
        $this->assertStringNotContainsString('content=', $body);
    }

    public function testEmptyExactParentReferenceStaysExact()
    {
        $body = $this->rewrite(
            '{filter field="tracker_field_child" exact="$parent.tracker_field_parent$"}',
            [['tracker_field_parent' => '']]
        );

        $this->assertStringContainsString('exact=""', $body);
        $this->assertStringNotContainsString('content=', $body);
    }

    public function testZeroParentContentStaysContentFilter()
    {
        $body = $this->rewrite(
            '{filter field="tracker_field_child" content="$parent.tracker_field_parent$"}',
            [['tracker_field_parent' => '0']]
        );

        $this->assertStringContainsString('content="0"', $body);
        $this->assertStringNotContainsString('exact=', $body);
    }

    public function testNonEmptyParentContentStaysContentFilter()
    {
        $body = $this->rewrite(
            '{filter field="tracker_field_child" content="$parent.tracker_field_parent$"}',
            [
                ['tracker_field_parent' => 'alpha'],
                ['tracker_field_parent' => 'beta'],
            ]
        );

        $this->assertStringContainsString('content="alpha OR beta"', $body);
        $this->assertStringNotContainsString('exact=', $body);
    }

    private function rewrite(string $plugin, array $dataset): string
    {
        $record = new Record('children', new Parser());
        $record->setBody($plugin);

        $datasetProperty = new ReflectionProperty(Record::class, 'dataset');
        $datasetProperty->setValue($record, $dataset);

        $formatter = new Search_Formatter($this->createMock(Search_Formatter_Plugin_Interface::class));
        $executor = new class ($record, $formatter) extends Executor {
            public function rewrittenBody(): string
            {
                return $this->replacePlaceholders();
            }
        };

        return $executor->rewrittenBody();
    }
}
