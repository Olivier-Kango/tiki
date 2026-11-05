<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\WikiPlugins;

class WikiPluginAliasPreferenceDeserializationTest extends \PHPUnit\Framework\TestCase
{
    public static bool $wakeupCalled = false;

    private array $originalPrefs = [];

    public function __wakeup(): void
    {
        self::$wakeupCalled = true;
    }

    protected function setUp(): void
    {
        global $prefs;

        self::$wakeupCalled = false;
        $this->originalPrefs = $prefs ?? [];

        require_once 'lib/prefs/wikiplugin.php';
        require_once 'lib/prefs/wikiplugininline.php';
    }

    protected function tearDown(): void
    {
        global $prefs;

        $prefs = $this->originalPrefs;
        self::$wakeupCalled = false;
    }

    public function testInfoRejectsSerializedObjectInAliasPreference(): void
    {
        global $prefs;

        $prefs['pluginalias_badalias'] = $this->createSerializedWakeupPayload();

        $result = \WikiPlugin_Negotiator_Wiki_Alias::info('badalias');

        $this->assertFalse($result);
        $this->assertFalse(
            self::$wakeupCalled,
            '__wakeup() must not run when alias preference is deserialized.'
        );
    }

    public function testGetListRejectsSerializedObjectInAliasListPreference(): void
    {
        global $prefs;

        $prefs['pluginaliaslist'] = $this->createSerializedWakeupPayload();

        $result = \WikiPlugin_Negotiator_Wiki_Alias::getList();

        $this->assertSame([], $result);
        $this->assertFalse(
            self::$wakeupCalled,
            '__wakeup() must not run when alias list preference is deserialized.'
        );
    }

    public function testPartialPluginPreferenceListsRejectSerializedObjectInAliasListPreference(): void
    {
        global $prefs;

        $prefs['pluginaliaslist'] = $this->createSerializedWakeupPayload();

        $pluginPrefs = \prefs_wikiplugin_list(true);
        $inlinePrefs = \prefs_wikiplugininline_list(true);

        $this->assertIsArray($pluginPrefs);
        $this->assertIsArray($inlinePrefs);
        $this->assertFalse(
            self::$wakeupCalled,
            '__wakeup() must not run while rebuilding plugin preference lists.'
        );
    }

    public function testTikiLibUnserializeRejectsObjectsByDefault(): void
    {
        $result = \TikiLib::unserialize($this->createSerializedWakeupPayload());

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $result);
        $this->assertFalse(
            self::$wakeupCalled,
            '__wakeup() must not run when TikiLib::unserialize() rejects classes.'
        );
    }

    public function testTikiLibUnserializeRestoresArrays(): void
    {
        $payload = ['plugin_name' => 'alias', 'implementation' => 'html'];

        $this->assertSame($payload, \TikiLib::unserialize(serialize($payload)));
    }

    private function createSerializedWakeupPayload(): string
    {
        // Avoid PHPUnit's required constructor while still providing a named class
        // whose __wakeup() can detect unsafe unserialize().
        $payload = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();

        return serialize($payload);
    }
}
