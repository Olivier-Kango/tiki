<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Tiki_PublicOriginTest extends PHPUnit\Framework\TestCase
{
    public function testPublicOrigins(): void
    {
        $defaults = ['http_port' => 80, 'https_port' => 443, 'https_login' => 'allowed'];
        $server = ['HTTP_HOST' => 'example.test', 'SERVER_PORT' => 80];
        $cases = [
            [[], [], 'http', ''],
            [['HTTPS' => 'on'], [], 'https', ''],
            [['SERVER_PORT' => 443], [], 'https', ''],
            [[], ['https_login' => 'force_nocheck'], 'https', ''],
            [['HTTP_HOST' => 'example.test:8080', 'SERVER_PORT' => 8080], [], 'http', 8080],
            [['HTTP_HOST' => 'example.test:8443', 'HTTPS' => 'on'], [], 'https', 8443],
            [['HTTP_HOST' => 'example.test:80', 'HTTPS' => 'on'], [], 'https', 80],
            [['HTTP_X_FORWARDED_PROTO' => 'https'], [], 'http', ''],
            [['HTTP_X_FORWARDED_PROTO' => 'https'], ['feature_port_rewriting' => 'y'], 'https', ''],
            [['HTTP_X_FORWARDED_PROTO' => 'https', 'SERVER_PORT' => 8080], ['feature_port_rewriting' => 'y'], 'https', ''],
            [['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '8443'], ['feature_port_rewriting' => 'y'], 'https', 8443],
            [['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => 'invalid'], ['feature_port_rewriting' => 'y'], 'https', ''],
            [['HTTP_X_FORWARDED_PROTO' => 'invalid'], ['feature_port_rewriting' => 'y'], 'http', ''],
            [['HTTP_X_FORWARDED_PROTO' => 'https'], ['feature_port_rewriting' => 'y', 'https_port' => 9443], 'https', 9443],
            [['HTTP_HOST' => '[::1]:8443', 'HTTPS' => 'on'], [], 'https', 8443],
        ];
        foreach ($cases as [$changes, $preferences, $scheme, $port]) {
            $origin = \Tiki\Url::publicOrigin(array_replace($server, $changes), array_replace($defaults, $preferences));
            $this->assertSame($scheme, $origin['scheme']);
            $this->assertSame($port, $origin['port']);
        }
    }
}
