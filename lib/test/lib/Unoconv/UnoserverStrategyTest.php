<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\Lib\Unoconv;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tiki\Lib\Unoconv\UnoserverStrategy;

/**
 * Checks the command line built by UnoserverStrategy using a fake `unoconvert` binary
 * that logs its arguments, so no real unoserver is needed.
 */
class UnoserverStrategyTest extends TestCase
{
    private string $tmpDir;
    private string $argsLog;
    private string $source;
    private string $destination;
    private $originalBinary;
    private $originalPrefs;
    private ReflectionProperty $binaryProperty;

    protected function setUp(): void
    {
        global $prefs;

        $this->tmpDir = sys_get_temp_dir() . '/tiki_unoserver_test_' . uniqid();
        mkdir($this->tmpDir);

        $this->argsLog = $this->tmpDir . '/args.log';
        $fake = $this->tmpDir . '/unoconvert';
        $script = <<<'SH'
#!/bin/sh
: > "$ARGS_LOG"
last=""
for arg in "$@"; do
    printf '%s\n' "$arg" >> "$ARGS_LOG"
    last="$arg"
done
: > "$last"
exit 0
SH;
        file_put_contents($fake, str_replace('$ARGS_LOG', $this->argsLog, $script));
        chmod($fake, 0755);

        $this->binaryProperty = new ReflectionProperty(UnoserverStrategy::class, 'unoServerBinary');
        $this->originalBinary = $this->binaryProperty->getValue();
        $this->binaryProperty->setValue(null, $fake);

        $this->originalPrefs = $prefs['alchemy_unoserver_port'] ?? null;
        $prefs['alchemy_unoserver_port'] = 2099;

        $this->source = $this->tmpDir . '/source.txt';
        $this->destination = $this->tmpDir . '/out.pdf';
        file_put_contents($this->source, 'hello');
    }

    protected function tearDown(): void
    {
        global $prefs;

        $this->binaryProperty->setValue(null, $this->originalBinary);

        if ($this->originalPrefs === null) {
            unset($prefs['alchemy_unoserver_port']);
        } else {
            $prefs['alchemy_unoserver_port'] = $this->originalPrefs;
        }

        foreach (glob($this->tmpDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    private function convert(string $format = 'pdf', ?string $pageRange = null): array
    {
        $this->assertTrue((new UnoserverStrategy())->convertFile($this->source, $this->destination, $format, $pageRange));

        return file($this->argsLog, FILE_IGNORE_NEW_LINES);
    }

    private function assertOption(array $args, string $option, string $value): void
    {
        $index = array_search($option, $args, true);
        $this->assertNotFalse($index, "Option $option was not passed: " . implode(' ', $args));
        $this->assertSame($value, $args[$index + 1] ?? null);
    }

    public function testHostLocationRemoteIsPassed(): void
    {
        $this->assertOption($this->convert(), '--host-location', 'remote');
    }

    public function testPortComesFromPreference(): void
    {
        $this->assertOption($this->convert(), '--port', '2099');
    }

    public function testConvertToFormatIsPassed(): void
    {
        $this->assertOption($this->convert('pdf'), '--convert-to', 'pdf');
    }

    public function testValidPageRangeUsesFilterOption(): void
    {
        $args = $this->convert('pdf', '1-1');

        $this->assertOption($args, '--filter-option', 'PageRange=1-1');
        $this->assertNotContains('-e', $args);
    }

    public function testInvalidPageRangeAddsNoFilterOption(): void
    {
        $args = $this->convert('pdf', '1;rm');

        $this->assertNotContains('--filter-option', $args);
    }
}
