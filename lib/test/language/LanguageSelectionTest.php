<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Language;

use Language;
use ReflectionProperty;
use Smarty\CompilerException;
use Tiki\Cache\CacheLib;
use Tiki\Sections;
use Tiki\Smarty\SmartyTiki;
use TikiLib;
use UsersLib;

class LanguageSelectionTest extends \TikiTestCase
{
    private const GLOBAL_NAMES = ['prefs', 'user', 'user_preferences', 'tikilib', 'flagmapping', '_GET', '_SESSION', '_SERVER'];

    private array $savedGlobals;
    private array $savedLibraries;
    private array $savedCallbacks;
    private ?string $savedSection;
    private array $savedLanguages;
    private TikiLib $tiki;
    private SmartyTiki $smarty;
    private SmartyTiki $realSmarty;

    protected function setUp(): void
    {
        $this->savedGlobals = [];
        foreach (self::GLOBAL_NAMES as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $this->savedGlobals[$name] = $GLOBALS[$name];
            }
        }
        $this->savedLibraries = (new ReflectionProperty(TikiLib::class, 'libraries'))->getValue();
        $this->savedCallbacks = (new ReflectionProperty(Sections::class, 'sectionChangeCallbacks'))->getValue();
        $this->savedSection = Sections::getCurrentSection();
        foreach (['userLanguage', 'adminLanguage'] as $name) {
            $property = new ReflectionProperty(Language::class, $name);
            $this->savedLanguages[$name] = $property->getValue();
            $property->setValue(null, null);
        }
        $this->realSmarty = TikiLib::lib('smarty');

        (new ReflectionProperty(Sections::class, 'sectionChangeCallbacks'))->setValue(null, []);
        $GLOBALS['prefs'] = array_merge($GLOBALS['prefs'], [
            'language' => 'en',
            'language_admin' => 'fr',
            'restrict_language' => 'n',
            'available_languages' => [],
            'feature_multilingual' => 'y',
            'change_language' => 'y',
            'feature_detect_language' => 'n',
            'theme_unified_admin_backend' => 'y',
            'lang_use_db' => 'n',
        ]);
        $GLOBALS['user'] = 'language-selection-test';
        $GLOBALS['user_preferences'] = ['language-selection-test' => ['language' => 'en']];
        $_GET = [];
        $_SESSION['preferences'] = [];
        Sections::setCurrentSection(Sections::SECTION_WIKI);

        $this->tiki = $this->createMock(TikiLib::class);
        $this->tiki->method('userHasPreference')->willReturnCallback(static function ($name) {
            global $user, $user_preferences;
            return $user ? isset($user_preferences[$user][$name]) : isset($_SESSION['preferences'][$name]);
        });
        $this->tiki->method('get_user_preference')->willReturnCallback(static function ($user, $name, $default = null) {
            return $user ? ($GLOBALS['user_preferences'][$user][$name] ?? $default) : ($_SESSION['preferences'][$name] ?? $default);
        });
        $this->smarty = $this->createMock(SmartyTiki::class);
        $this->setLibraries();
        $GLOBALS['tikilib'] = $this->tiki;
    }

    protected function tearDown(): void
    {
        foreach (self::GLOBAL_NAMES as $name) {
            if (array_key_exists($name, $this->savedGlobals)) {
                $GLOBALS[$name] = $this->savedGlobals[$name];
            } else {
                unset($GLOBALS[$name]);
            }
        }
        (new ReflectionProperty(TikiLib::class, 'libraries'))->setValue(null, $this->savedLibraries);
        (new ReflectionProperty(Sections::class, 'sectionChangeCallbacks'))->setValue(null, $this->savedCallbacks);
        (new ReflectionProperty(Sections::class, 'currentSection'))->setValue(null, $this->savedSection);
        foreach ($this->savedLanguages as $name => $language) {
            (new ReflectionProperty(Language::class, $name))->setValue(null, $language);
        }
        $this->realSmarty->refreshLanguage();
    }

    private function setLibraries(array $libraries = []): void
    {
        $libraries = array_merge($this->savedLibraries, ['tiki' => $this->tiki, 'smarty' => $this->smarty], $libraries);
        (new ReflectionProperty(TikiLib::class, 'libraries'))->setValue(null, $libraries);
    }

    private function setSection(string $section): void
    {
        Sections::setCurrentSection($section);
        Language::setSectionLanguage($section);
    }

    private function applyLanguageRequest(): void
    {
        include 'lib/setup/language.php';
        Language::setSectionLanguage(Sections::getCurrentSection());
    }

    private function expectPreferenceWrite(string $name, string $value): void
    {
        $this->tiki->expects($this->once())->method('set_user_preference')
            ->with($GLOBALS['user'], $name, $value)
            ->willReturnCallback(static function ($user, $name, $value) {
                if ($name !== 'language') {
                    $GLOBALS['prefs'][$name] = $value;
                }
                if ($user) {
                    $GLOBALS['user_preferences'][$user][$name] = $value;
                } else {
                    $_SESSION['preferences'][$name] = $value;
                }
                return true;
            });
    }

    /** @dataProvider adminSections */
    public function testAdminLanguageIsTemporary(string $section, string $adminLanguage): void
    {
        $GLOBALS['prefs']['language_admin'] = $adminLanguage;
        $GLOBALS['prefs']['language'] = $adminLanguage === '' ? 'fr' : 'en';
        $GLOBALS['prefs']['feature_multilingual'] = 'n';
        $GLOBALS['prefs']['change_language'] = 'n';
        $this->tiki->expects($this->never())->method('set_user_preference');

        $this->setSection($section);
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->assertSame('en', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
        $this->assertSame($adminLanguage === '' ? 'fr' : 'en', $GLOBALS['prefs']['language']);
        $this->assertSame($adminLanguage, $GLOBALS['prefs']['language_admin']);

        $this->setSection(Sections::SECTION_WIKI);
        $this->assertSame('en', Language::getCurrentLanguage());
    }

    public static function adminSections(): array
    {
        return [
            'admin' => [Sections::SECTION_ADMIN, 'fr'],
            'default admin layout' => [Sections::SECTION_ADMIN_LAYOUT, ''],
        ];
    }

    /** @dataProvider explicitSections */
    public function testSectionLanguageUsesTheSuppliedSection(?string $section, string $expectedLanguage): void
    {
        $currentSection = $expectedLanguage === 'fr' ? Sections::SECTION_WIKI : Sections::SECTION_ADMIN;
        Sections::setCurrentSection($currentSection);
        $this->tiki->expects($this->never())->method('set_user_preference');

        Language::setSectionLanguage($section);

        $this->assertSame($expectedLanguage, Language::getSectionLanguage($section));
        $this->assertSame($currentSection, Sections::getCurrentSection());
        $this->assertSame($expectedLanguage === 'fr' ? 'en' : 'fr', Language::getCurrentLanguage());
    }

    public static function explicitSections(): array
    {
        return [
            'admin' => [Sections::SECTION_ADMIN, 'fr'],
            'admin layout' => [Sections::SECTION_ADMIN_LAYOUT, 'fr'],
            'regular page' => [Sections::SECTION_WIKI, 'en'],
            'before section selection' => [null, 'en'],
        ];
    }

    /** @dataProvider missingOrInvalidUserLanguages */
    public function testSiteDefaultFollowsChangesWithoutSavingAPersonalChoice(?string $language): void
    {
        $preferences = $language === null ? [] : ['language' => $language];
        $GLOBALS['user_preferences'][$GLOBALS['user']] = $preferences;
        $GLOBALS['prefs']['language'] = 'de';
        $this->tiki->expects($this->never())->method('set_user_preference');

        Language::setSectionLanguage(Sections::SECTION_WIKI);
        $this->assertSame('de', Language::resolveUserLanguage());
        $this->setSection(Sections::SECTION_ADMIN);
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->setSection(Sections::SECTION_WIKI);
        $this->assertSame('de', Language::getCurrentLanguage());

        $GLOBALS['prefs']['language'] = 'fr';
        Language::setSectionLanguage(Sections::SECTION_WIKI);
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->assertSame($preferences, $GLOBALS['user_preferences'][$GLOBALS['user']]);
    }

    public static function missingOrInvalidUserLanguages(): array
    {
        return ['missing preference' => [null], 'empty preference' => [''], 'invalid preference' => ['../lang/en']];
    }

    public function testBrowserDetectionFollowsChangesOnlyWithoutASavedChoice(): void
    {
        $GLOBALS['prefs']['feature_detect_language'] = 'y';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
        $this->tiki->expects($this->never())->method('set_user_preference');

        Language::setSectionLanguage(Sections::SECTION_WIKI);
        $this->assertSame('en', Language::getCurrentLanguage());

        $GLOBALS['user_preferences'][$GLOBALS['user']] = [];
        Language::setSectionLanguage(Sections::SECTION_WIKI);
        $this->assertSame('fr', Language::getCurrentLanguage());

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';
        Language::setSectionLanguage(Sections::SECTION_WIKI);
        $this->assertSame('de', Language::getCurrentLanguage());
        $this->assertSame([], $GLOBALS['user_preferences'][$GLOBALS['user']]);
    }

    public function testAnonymousAdminOverrideDoesNotChangeSessionPreferences(): void
    {
        $GLOBALS['user'] = '';
        $GLOBALS['user_preferences'] = [];
        $_SESSION['preferences']['language'] = 'en';
        $this->tiki->expects($this->never())->method('set_user_preference');
        $this->setSection(Sections::SECTION_ADMIN);
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->assertSame('en', $_SESSION['preferences']['language']);
        $this->setSection(Sections::SECTION_WIKI);
        $this->assertSame('en', Language::getCurrentLanguage());
    }

    public function testAnonymousExplicitSwitchIsSavedInSession(): void
    {
        $GLOBALS['user'] = '';
        $GLOBALS['user_preferences'] = [];
        $_GET['switchLang'] = 'fr';
        $this->expectPreferenceWrite('language', 'fr');
        $this->applyLanguageRequest();
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->assertSame('fr', $_SESSION['preferences']['language']);
    }

    /** @dataProvider ignoredSwitchRequests */
    public function testIgnoredSwitchRequestDoesNotChangeOrSaveLanguage(mixed $language, array $preferences): void
    {
        $GLOBALS['prefs'] = array_merge($GLOBALS['prefs'], $preferences);
        $_GET['switchLang'] = $language;
        $this->tiki->expects($this->never())->method('set_user_preference');
        $this->applyLanguageRequest();
        $this->assertSame('en', Language::getCurrentLanguage());
    }

    public static function ignoredSwitchRequests(): array
    {
        return [
            'switching disabled' => ['fr', ['change_language' => 'n']],
            'multilingual disabled' => ['fr', ['feature_multilingual' => 'n']],
            'restricted language' => ['fr', ['restrict_language' => 'y', 'available_languages' => ['en']]],
            'unchanged choice' => ['en', []],
            'empty input' => ['', []],
            'array input' => [['fr'], []],
            'path traversal' => ['../lang/en', []],
            'unknown language' => ['not-a-language', []],
        ];
    }

    public function testSectionSelectionDoesNotProcessExplicitRequests(): void
    {
        $_GET['switchLang'] = 'fr';
        $this->tiki->expects($this->never())->method('set_user_preference');

        Language::setSectionLanguage(Sections::getCurrentSection());

        $this->assertSame('en', Language::getCurrentLanguage());
        $this->assertSame('en', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
    }

    /** @dataProvider adminChoiceSections */
    public function testAdminChoicePreservesRegularLanguage(string $section, string $expectedLanguage): void
    {
        $this->setSection($section);
        $this->expectPreferenceWrite('language_admin', 'de');
        $this->assertTrue(Language::setCurrentLanguage('de', true));
        $this->assertSame($expectedLanguage, Language::getCurrentLanguage());
        $this->assertSame('de', $GLOBALS['user_preferences'][$GLOBALS['user']]['language_admin']);
        $this->assertSame('en', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);

        $this->setSection(Sections::SECTION_ADMIN);
        $this->assertSame('de', Language::getCurrentLanguage());
        $this->setSection(Sections::SECTION_WIKI);
        $this->assertSame('en', Language::getCurrentLanguage());
    }

    public static function adminChoiceSections(): array
    {
        return [
            'regular page' => [Sections::SECTION_WIKI, 'en'],
            'admin page' => [Sections::SECTION_ADMIN, 'de'],
        ];
    }

    public function testExplicitSwitchLangIsSavedBeforeAdminOverride(): void
    {
        $_GET['switchLang'] = 'de';
        $this->expectPreferenceWrite('language', 'de');
        $this->setSection(Sections::SECTION_ADMIN);
        $this->applyLanguageRequest();
        $this->assertSame('fr', Language::getCurrentLanguage());
        $this->assertSame('de', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
        unset($_GET['switchLang']);
        $this->setSection(Sections::SECTION_WIKI);
        $this->assertSame('de', Language::getCurrentLanguage());
    }

    public function testExplicitFrontendSwitchIsAppliedWhenRendering(): void
    {
        $GLOBALS['prefs']['smarty_enable_string_eval'] = 'y';
        $this->realSmarty->refreshLanguage();
        $this->expectPreferenceWrite('language', 'fr');
        $this->smarty->expects($this->never())->method('refreshLanguage');
        $this->assertTrue(Language::setCurrentLanguage('fr'));
        $this->assertSame('fr', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
        $this->assertSame('fr', Language::getCurrentLanguage());

        $this->setLibraries(['smarty' => $this->realSmarty]);
        $this->assertSame('fr', $this->realSmarty->fetch('eval:{Language::getCurrentLanguage()}'));
        $this->assertStringStartsWith('fr-', $this->realSmarty->compile_id);
    }

    /** @dataProvider templateSections */
    public function testCurrentLanguageUsesResolvedProperties(string $section, string $expectedLanguage): void
    {
        $this->setSection($section);
        $GLOBALS['prefs']['language'] = 'de';
        $GLOBALS['prefs']['language_admin'] = 'de';
        $GLOBALS['user_preferences'][$GLOBALS['user']]['language'] = 'de';

        $this->assertSame($expectedLanguage, Language::getCurrentLanguage());
    }

    public function testStartupLanguageDoesNotRequireLoadedPreferences(): void
    {
        unset($GLOBALS['prefs'], $GLOBALS['user_preferences'], $GLOBALS['tikilib']);
        $this->tiki->expects($this->never())->method('get_user_preference');
        Language::setSectionLanguage(Sections::getCurrentSection(), 'de');

        $this->assertSame('de', Language::getCurrentLanguage());
    }

    /** @dataProvider templateSections */
    public function testFetchLangPreservesChoicesAndRestoresSectionLanguage(string $section, string $expectedLanguage): void
    {
        $this->tiki->expects($this->never())->method('set_user_preference');
        $this->setLibraries(['smarty' => $this->realSmarty]);
        $this->setSection($section);

        $template = 'eval:{tr}Your account information for %s{/tr}|{$currentRenderingLanguage}'
            . '|{Language::resolveUserLanguage()}|{Language::resolveAdminLanguage()}'
            . '|{$prefs.language}|{$prefs.language_admin}';
        $this->assertSame(
            tra('Your account information for %s', 'de') . '|de|en|fr|en|fr',
            $this->realSmarty->fetchLang('de', $template)
        );
        $this->assertSame($expectedLanguage, Language::getCurrentLanguage());
        $this->assertSame($expectedLanguage, $this->realSmarty->getTemplateVars('currentRenderingLanguage'));
        $this->assertStringStartsWith($expectedLanguage . '-', $this->realSmarty->compile_id);
        $this->assertSame('en', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
    }

    public static function templateSections(): array
    {
        return [
            'regular page' => [Sections::SECTION_WIKI, 'en'],
            'admin page' => [Sections::SECTION_ADMIN, 'fr'],
        ];
    }

    public function testFetchLangRestoresAnEnclosingRenderingLanguage(): void
    {
        $this->setLibraries(['smarty' => $this->realSmarty]);
        $this->setSection(Sections::SECTION_ADMIN);
        Language::setSectionLanguage(Sections::getCurrentSection(), 'de');

        $this->assertSame('en', $this->realSmarty->fetchLang('en', 'eval:{$currentRenderingLanguage}'));
        $this->assertSame('de', Language::getCurrentLanguage());
        $this->assertSame('de', $this->realSmarty->getTemplateVars('currentRenderingLanguage'));
        $this->assertSame('en', Language::resolveUserLanguage());
        $this->assertSame('fr', Language::resolveAdminLanguage());
        $this->assertStringStartsWith('de-', $this->realSmarty->compile_id);

        Language::setSectionLanguage(Sections::getCurrentSection(), null);
        $this->assertSame('fr', Language::getCurrentLanguage());
    }

    public function testRegularChoiceUpdatesAdminFallback(): void
    {
        $GLOBALS['prefs']['language_admin'] = '../lang/en';
        $this->setSection(Sections::SECTION_ADMIN);
        $this->assertSame('en', Language::getCurrentLanguage());
        $this->expectPreferenceWrite('language', 'fr');

        $this->assertTrue(Language::setCurrentLanguage('fr'));
        $this->assertSame('fr', Language::resolveUserLanguage());
        $this->assertSame('fr', Language::resolveAdminLanguage());
        $this->assertSame('fr', Language::getCurrentLanguage());
    }

    /** @dataProvider failedRenderingContexts */
    public function testFetchLangRestoresLanguageAfterRenderingFailure(string $section, ?string $override, string $expectedLanguage): void
    {
        $this->tiki->expects($this->never())->method('set_user_preference');
        $this->setLibraries(['smarty' => $this->realSmarty]);
        $this->setSection($section);
        Language::setSectionLanguage(Sections::getCurrentSection(), $override);
        $this->expectException(CompilerException::class);
        try {
            $this->realSmarty->fetchLang('fr', 'string:{unknown_language_regression_tag}');
        } finally {
            $this->assertSame($expectedLanguage, Language::getCurrentLanguage());
            $this->assertSame($expectedLanguage, $this->realSmarty->getTemplateVars('currentRenderingLanguage'));
            $this->assertStringStartsWith($expectedLanguage . '-', $this->realSmarty->compile_id);
            $this->assertSame('en', $GLOBALS['prefs']['language']);
            $this->assertSame('en', $GLOBALS['user_preferences'][$GLOBALS['user']]['language']);
        }
    }

    public static function failedRenderingContexts(): array
    {
        return [
            'regular page' => [Sections::SECTION_WIKI, null, 'en'],
            'admin page' => [Sections::SECTION_ADMIN, null, 'fr'],
            'regular override' => [Sections::SECTION_WIKI, 'de', 'de'],
            'admin override' => [Sections::SECTION_ADMIN, 'de', 'de'],
        ];
    }

    public function testTemplateCacheUsesRequestedLanguageAndRestoresAdminLanguage(): void
    {
        $this->tiki->expects($this->never())->method('set_user_preference');
        $this->setLibraries(['smarty' => $this->realSmarty]);
        $this->setSection(Sections::SECTION_ADMIN);
        $this->realSmarty->refreshLanguage();
        $compileId = $this->realSmarty->compile_id;
        $preferences = $GLOBALS['prefs'];
        $directory = 'templates/language-selection-' . bin2hex(random_bytes(6));
        $compileDirectory = sys_get_temp_dir() . '/' . basename($directory);
        $previousCompileDirectory = $this->realSmarty->getCompileDir();
        mkdir($directory);
        mkdir($compileDirectory);
        $file = $directory . '/language.tpl';
        file_put_contents($file, '{tr}Your account information for %s{/tr}');
        $this->realSmarty->setCompileDir($compileDirectory);

        try {
            (new CacheLib())->cache_templates($directory, 'de');

            $compiledFiles = glob($compileDirectory . '/*.php');
            $this->assertCount(1, $compiledFiles);
            $this->assertStringContainsString(tra('Your account information for %s', 'de'), file_get_contents($compiledFiles[0]));
            $this->assertSame('fr', Language::getCurrentLanguage());
            $this->assertSame('fr', $this->realSmarty->getTemplateVars('currentRenderingLanguage'));
            $this->assertSame('en', Language::resolveUserLanguage());
            $this->assertSame('fr', Language::resolveAdminLanguage());
            $this->assertSame($compileId, $this->realSmarty->compile_id);
            $this->assertSame($preferences, $GLOBALS['prefs']);
        } finally {
            $this->realSmarty->setCompileDir($previousCompileDirectory);
            foreach (glob($compileDirectory . '/*.php') as $compiledFile) {
                unlink($compiledFile);
            }
            rmdir($compileDirectory);
            unlink($file);
            rmdir($directory);
        }
    }

    /** @dataProvider pickers */
    public function testPickerUsesItsOwnLanguageDuringTemporaryRendering(string $module, string $mode, string $section, string $expectedLanguage): void
    {
        require_once 'modules/mod-func-' . $module . '.php';
        $this->tiki->expects($this->never())->method('set_user_preference');
        $userlib = $this->createMock(UsersLib::class);
        $userlib->method('user_has_permission')->willReturn(true);
        $langlib = TikiLib::lib('language');
        $this->setLibraries(['user' => $userlib, 'language' => $langlib]);
        $assigned = [];
        $this->smarty->method('assign_by_ref')->willReturnCallback(static function ($name, &$value) use (&$assigned) {
            $assigned[$name] = $value;
        });

        $this->setSection($section);
        Language::setSectionLanguage($section, 'de');
        $activeLanguage = Language::getCurrentLanguage();
        $function = 'module_' . $module;
        $function(null, ['mode' => $mode]);

        $this->assertSame($activeLanguage, Language::getCurrentLanguage());
        $this->assertSame($expectedLanguage, $assigned['frontendLang']);
        if ($mode === 'flags') {
            $highlighted = array_filter($assigned['languages'], static fn($language) => str_contains($language['class'], 'highlight'));
            $this->assertSame([$expectedLanguage], array_column($highlighted, 'value'));
        }
    }

    public static function pickers(): array
    {
        return [
            'admin dropdown on regular page' => ['switch_lang_admin', 'droplist', Sections::SECTION_WIKI, 'fr'],
            'admin flags on regular page' => ['switch_lang_admin', 'flags', Sections::SECTION_WIKI, 'fr'],
            'regular dropdown on admin page' => ['switch_lang', 'droplist', Sections::SECTION_ADMIN, 'en'],
            'regular flags on admin page' => ['switch_lang', 'flags', Sections::SECTION_ADMIN, 'en'],
            'admin dropdown on admin page' => ['switch_lang_admin', 'droplist', Sections::SECTION_ADMIN, 'fr'],
            'admin flags on admin page' => ['switch_lang_admin', 'flags', Sections::SECTION_ADMIN, 'fr'],
            'regular dropdown on regular page' => ['switch_lang', 'droplist', Sections::SECTION_WIKI, 'en'],
            'regular flags on regular page' => ['switch_lang', 'flags', Sections::SECTION_WIKI, 'en'],
        ];
    }
}
