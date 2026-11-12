<?php

declare(strict_types=1);

namespace Tiki\Tests\Core\Search;

use ErrorException;
use TikiLib;
use TikiTestCase;

/**
 * @group unit
 */
class UnifiedSearchLogItemsTest extends TikiTestCase
{
    private array $savedPrefs = [];
    private string $tmpDir;

    protected function setUp(): void
    {
        global $prefs;
        $this->savedPrefs = ['tmpDir' => $prefs['tmpDir'], 'unified_engine' => $prefs['unified_engine']];
        $this->tmpDir = sys_get_temp_dir() . '/tiki-search-log-' . uniqid();
        mkdir($this->tmpDir);
        $prefs['tmpDir'] = $this->tmpDir;
        $prefs['unified_engine'] = 'elastic';
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs = array_merge($prefs, $this->savedPrefs);
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function testNoLogYetGivesNoItemsAndNoWarning(): void
    {
        set_error_handler(function (int $errno, string $errstr): bool {
            throw new ErrorException($errstr, 0, $errno);
        });
        try {
            $items = TikiLib::lib('unifiedsearch')->getLogItems(5, 'ERR');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $items);
    }

    public function testExistingLogIsStillRead(): void
    {
        $lib = TikiLib::lib('unifiedsearch');
        file_put_contents($lib->getLogFilename(1), "INFO indexed\nERR broken object\n");

        $items = $lib->getLogItems(5, 'ERR');

        $this->assertSame(["ERR broken object\n"], $items['web']['logs']);
        $this->assertArrayNotHasKey('console', $items);
    }
}
