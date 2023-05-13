<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OpenIdConnect;

use Lcobucci\JWT\Signer\Rsa\Sha256;

class RSA256Signer extends Sha256
{
    /**
     * {@inheritdoc}
     */
    public function verify($expected, $payload, $key)
    {
        if (is_array($key->contents())) {
            return ! empty(
                array_filter(
                    $key->contents(),
                    function ($content) use ($expected, $payload) {
                        return parent::verify($expected, $payload, $content);
                    }
                )
            );
        }

        return parent::verify($expected, $payload, $key);
    }
}
