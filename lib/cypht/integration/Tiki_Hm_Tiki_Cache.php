<?php

/**
 * Redis cache
 * @package framework
 * @subpackage cache
 */
class Tiki_Hm_Tiki_Cache
{
    private $cachelib;

    /**
     * @param Hm_Config $config site config object
     */
    public function __construct()
    {
        $this->cachelib = TikiLib::lib('cache');
    }

    /**
     * @param string $key name of value to cache
     * @param mixed $val value to cache
     * @param integer $lifetime how long to cache (if applicable for the backend)
     * @param boolean $session store in the session instead of the enabled cache
     * @return boolean
     */
    public function set($key, $val)
    {
        return $this->cachelib->cacheItem($key, $val);
    }

    /**
     * @param string $key name of value to fetch
     * @param mixed $default value to return if not found
     * @param boolean $session fetch from the session instead of the enabled cache
     * @return mixed
     */
    public function get($key, $default = false)
    {
        return $this->cachelib->getCached($key) ?? $default;
    }

    /**
     * @param string $key name to delete
     * @param boolean $session fetch from the session instead of the enabled cache
     * @return boolean
     */
    public function del($key)
    {
        return $this->cachelib->invalidate($key);
    }
}
