<?php

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256 as Hmac256;
use Lcobucci\JWT\Signer\Rsa\Sha256 as Rsa256;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use TokenEntityTrait;
    use EntityTrait;

    public function initJwtConfiguration()
    {
        if (is_null($this->privateKey) || (is_object($this->privateKey) && method_exists($this->privateKey, 'isNullKey') && $this->privateKey->isNullKey())) {
            $key = $this->getClient()->getClientSecret();
            $passPhrase = '';
            $signer = new Hmac256();
        } else {
            $key = $this->privateKey->getKeyContents();
            $passPhrase = $this->privateKey->getPassPhrase() ?? '';
            $signer = new Rsa256();
        }

        $this->jwtConfiguration = Configuration::forAsymmetricSigner(
            $signer,
            InMemory::plainText($key, $passPhrase),
            InMemory::plainText('empty', 'empty')
        );
    }
}
