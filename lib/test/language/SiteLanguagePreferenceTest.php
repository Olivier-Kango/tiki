<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Language;

use TikiDb;
use TikiLib;

class SiteLanguagePreferenceTest extends \TikiTestCase
{
    private array $savedGlobals;

    protected function setUp(): void
    {
        foreach (['prefs', 'user', 'user_preferences', '_SESSION'] as $name) {
            $this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
        }
        $GLOBALS['user'] = 'site-language-preference-test';
        $GLOBALS['prefs']['language'] = 'fr';
        $GLOBALS['user_preferences'][$GLOBALS['user']] = [];
        $_SESSION['preferences'] = [];
        TikiDb::get()->beginTransaction();
    }

    protected function tearDown(): void
    {
        TikiDb::get()->rollback();
        foreach ($this->savedGlobals as $name => $value) {
            $GLOBALS[$name] = $value;
        }
    }

    /** @dataProvider preferenceWriters */
    public function testPersonalWritesPreserveSiteLanguage(string $writer): void
    {
        $tiki = TikiLib::lib('tiki');
        if ($writer === 'anonymous') {
            $GLOBALS['user'] = null;
            $tiki->set_user_preference(null, 'language', 'de');
        } elseif ($writer === 'bulk') {
            $preferences = ['language' => 'de', 'realName' => 'QA name'];
            $tiki->set_user_preferences($GLOBALS['user'], $preferences);
            $this->assertSame('QA name', $GLOBALS['prefs']['realName']);
        } else {
            $tiki->set_user_preference($GLOBALS['user'], 'language', 'de');
        }

        $this->assertSame('fr', $GLOBALS['prefs']['language']);
        $this->assertSame('de', $tiki->get_user_preference($GLOBALS['user'], 'language'));
    }

    public static function preferenceWriters(): array
    {
        return ['single' => ['single'], 'bulk' => ['bulk'], 'anonymous' => ['anonymous']];
    }

    /** @dataProvider personalLanguageOwners */
    public function testClearingPersonalLanguagePreservesSiteLanguage(bool $anonymous): void
    {
        $tiki = TikiLib::lib('tiki');
        if ($anonymous) {
            $GLOBALS['user'] = null;
        }
        $user = $GLOBALS['user'];
        $tiki->set_user_preference($user, 'language', 'de');
        $tiki->set_user_preference($user, 'language', '');

        $this->assertSame('fr', $GLOBALS['prefs']['language']);
        if ($anonymous) {
            $this->assertArrayNotHasKey('language', $_SESSION['preferences']);
        } else {
            $this->assertSame('0', (string) $tiki->getOne(
                'SELECT COUNT(*) FROM tiki_user_preferences WHERE user = ? AND prefName = ?',
                [$user, 'language']
            ));
        }
    }

    public static function personalLanguageOwners(): array
    {
        return ['logged in' => [false], 'anonymous' => [true]];
    }

    public function testSiteLanguageSaveAndResetUpdateCanonicalValue(): void
    {
        $tiki = TikiLib::lib('tiki');
        $tiki->set_user_preference($GLOBALS['user'], 'language', 'it');
        $tiki->set_preference('language', 'de');
        $this->assertSame('de', $GLOBALS['prefs']['language']);
        $tiki->delete_preference('language');
        $this->assertSame('en', $GLOBALS['prefs']['language']);
        $this->assertSame('it', $tiki->get_user_preference($GLOBALS['user'], 'language'));
        $this->assertArrayNotHasKey('site_language', $GLOBALS['prefs']);
    }
}
