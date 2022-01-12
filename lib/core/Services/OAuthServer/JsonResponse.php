<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\PumpStream;
use Psr\Http\Message\StreamInterface;

class Services_OAuthServer_JsonResponse extends Response
{
    public function __construct($status = 200, array $headers = [], $body = null, $version = '1.1', $reason = null)
    {
        parent::__construct(
            $status,
            array_merge(
                ['Content-Type' => 'application/json'],
                $headers
            ),
            self::formatBody($body),
            $version,
            $reason
        );
    }

    public static function formatBody($input)
    {
        $options = JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE;

        if (is_null($input) || is_scalar($input) || is_array($input)) {
            return json_encode($input, $options);
        }

        if (is_object($input) && method_exists($input, '__toString')) {
            return json_encode((string)$input, $options);
        }
    }
}
