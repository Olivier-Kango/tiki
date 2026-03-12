<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_EmailActionTest extends TikiTestCase
{
    /**
     * @var Search_Action_EmailAction
     */
    private $action;

    /**
     * @var ReflectionMethod
     */
    private $dereferenceMethod;

    /**
     * @var TestableTikiLib|null
     */
    private $overrideLibs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new Search_Action_EmailAction();
        $this->dereferenceMethod = new ReflectionMethod(Search_Action_EmailAction::class, 'dereference');
        $this->dereferenceMethod->setAccessible(true);
        $this->overrideLibs = null;
    }

    protected function tearDown(): void
    {
        $this->overrideLibs = null;
        parent::tearDown();
    }

    public function testDereferenceParsesSimpleRfcAddressList()
    {
        $resolved = $this->invokeDereference('alice@example.org, bob@example.org');

        $this->assertSame(['alice@example.org', 'bob@example.org'], $resolved);
    }

    public function testDereferenceParsesNamedRfcAddressListWithQuotedCommas()
    {
        $resolved = $this->invokeDereference('"Doe, John" <john.doe@example.org>, Jane Doe <jane@example.org>');

        $this->assertSame(
            [
                'Doe, John' => 'john.doe@example.org',
                'Jane Doe' => 'jane@example.org',
            ],
            $resolved
        );
    }

    public function testDereferenceParsesRfcAddressListAcrossMultipleLines()
    {
        $resolved = $this->invokeDereference("Alice <alice@example.org>\nBob <bob@example.org>");

        $this->assertSame(
            [
                'Alice' => 'alice@example.org',
                'Bob' => 'bob@example.org',
            ],
            $resolved
        );
    }

    public function testDereferenceParsesMixedNamedAndPlainRfcAddresses()
    {
        $resolved = $this->invokeDereference('Alice <alice@example.org>, bob@example.org');

        $this->assertSame(
            [
                'Alice' => 'alice@example.org',
                0 => 'bob@example.org',
            ],
            $resolved
        );
    }

    public function testDereferenceFallsBackToCommaSeparatedTikiUsernames()
    {
        $trkLib = $this->createMock(get_class(TikiLib::lib('trk')));
        $trkLib->expects($this->once())
            ->method('parse_user_field')
            ->with('alice,bob')
            ->willReturn(['alice', 'bob']);

        $userLib = $this->createMock(get_class(TikiLib::lib('user')));
        $userLib->expects($this->exactly(2))
            ->method('get_user_email')
            ->willReturnMap([
                ['alice', 'alice@example.org'],
                ['bob', 'bob@example.org'],
            ]);

        $this->overrideLibs = new TestableTikiLib();
        $this->overrideLibs->overrideLibs([
            'trk' => $trkLib,
            'user' => $userLib,
        ]);

        $resolved = $this->invokeDereference('alice,bob');

        $this->assertSame(['alice@example.org', 'bob@example.org'], $resolved);
    }

    public function testDereferenceReturnsEmptyForInvalidInputContainingAtSign()
    {
        $resolved = $this->invokeDereference('not-an-email@');

        $this->assertSame([], $resolved);
    }

    public function testDereferenceStripsNoParseMarkersBeforeParsing()
    {
        $resolved = $this->invokeDereference('~np~"Alice, Test" <alice@example.org>~/np~');

        $this->assertSame(
            [
                'Alice, Test' => 'alice@example.org',
            ],
            $resolved
        );
    }

    private function invokeDereference(string $value): array
    {
        return $this->dereferenceMethod->invoke($this->action, $value);
    }
}
