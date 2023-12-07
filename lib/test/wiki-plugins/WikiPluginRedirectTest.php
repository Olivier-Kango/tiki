<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *  Test wiki page rendering options
 */

namespace test\Tiki\Lib\wiki;

use Exception;
use PHPUnit\Framework\TestCase;
use TikiLib;

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_redirect.php');

class WikiPluginRedirectTest extends TestCase
{
    public function testRedirectInPrintMode(): void
    {
        TikiLib::lib('parser')->option['print'] = 'y';
        try {
            $result = wikiplugin_redirect(["data" => "data to be parsed"], ["url" => "https://dev.tiki.org"]);
            $this->assertEquals("", $result);
        } catch (Exception $e) {
            $this->fail("Unexpected error: " . $e->getMessage());
        }
    }
}
