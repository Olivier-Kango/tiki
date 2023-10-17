<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Test\TestHelpers;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class WebClientHelper
{
    /**
     * @var bool $followRedirects if the client should automatically follow redirects
     *
     * @return HttpBrowser
     */
    public static function createTestClient($followRedirects = true): HttpBrowser
    {
        $browser = new HttpBrowser(HttpClient::create([
            'max_redirects' => $followRedirects ? 20 : 0,
            'verify_host' => false,
            'verify_peer' => false,
        ]));
        $browser->getCookieJar()->clear();
        return $browser;
    }
}
