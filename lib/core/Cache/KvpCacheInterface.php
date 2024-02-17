<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Cache;

interface TikiKvpCacheInterface
{
    /** Is this cache system enabled and functional */
    public function isFunctionnal(): bool;

    public function cacheItem($key, $data, $type = '');

    /**
     * @deprecated It does not make sense to do this rather than getCached()
     *
     * @param [type] $key
     * @param string $type
     * @return boolean
     */
    public function isCached($key, $type = '');

    public function getCached($key, $type = '', $lastModif = false);

    public function invalidate($key, $type = '');

    public function empty_type_cache($type);
}
