<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Test\Lib\Perspective;

use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Response;
use Tiki\Test\TestHelpers\TikiDbHelper;
use Tiki\Test\TestHelpers\TikiProfileHelper;
use Tiki\Test\TestHelpers\WebClientHelper;

/**
 * @group RequiresWebServer
 */
class PerspectivesNavigationBaseTestCase extends TestCase
{
    /**
     * Value used in the fixture files for TIKI_TEST_HOST
     */
    public const FIXTURE_HOST = 'tiki.localdomain';
    /**
     * Value used in the fixture files for TIKI_TEST_HOST_A
     */
    public const FIXTURE_SITE = 'tiki-a.localdomain';

    public static $cached_prefs;

    public static function setUpBeforeClass(): void
    {
        if (! getenv('TIKI_TEST_HOST') || ! getenv('TIKI_TEST_HOST_A') || ! getenv('TIKI_TEST_HOST_B')) {
            self::markTestSkipped(
                'To run perspective tests you are expected to have a running webserver with 3 vhosts pointing to it and to setup the env TIKI_TEST_HOST, TIKI_TEST_HOST_A and TIKI_TEST_HOST_B'
            );
        }
        global $prefs;
        self::$cached_prefs = $prefs;
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;
        foreach (self::$cached_prefs as $name => $val) {
            $def = \TikiLib::lib('prefs')->getPreference($name);
            if ($def && ! empty($def['available'])) {
                \TikiLib::lib('tiki')->set_preference($name, $val);
            } else {
                $prefs[$name] = $val;
            }
        }
    }

    public function navigateSteps($steps, $cleanCookies = false)
    {
        $client = WebClientHelper::createTestClient(false);
        $client->followRedirects(false);

        foreach ($steps as $stepIndex => $step) {
            [$url, $httpCode, $location, $perspective] = $step;

            if ($cleanCookies) {
                $client->getCookieJar()->clear();
            }

            if (empty($url) || $url === 'follow-redirect') {
                $crawler = $client->followRedirect();
            } else {
                $crawler = $client->request('GET', $url);
            }

            /** @var Response $response */
            $response = $client->getResponse();

            $this->assertEquals($httpCode, $response->getStatusCode(), 'Comparing HTTP Code #' . $stepIndex);

            if (! empty($location)) {
                $this->assertEquals(
                    $location,
                    $response->getHeader('Location'),
                    'Comparing Location header returned #' . $stepIndex
                );
            }

            if (! empty($perspective)) {
                if ($perspective[0] === '!') {
                    $this->assertStringNotContainsString(
                        substr($perspective, 1),
                        $crawler->filter('body')->attr('class'),
                        'Page shows right perspective #' . $stepIndex
                    );
                } else {
                    $this->assertStringContainsString(
                        $perspective,
                        $crawler->filter('body')->attr('class'),
                        'Page shows right perspective #' . $stepIndex
                    );
                }
            }
        }
    }
}
