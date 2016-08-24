<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Cache\Storage;

use Cygnite\Cache\StorageInterface;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite Memcache Cache Wrapper Class.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Memcached implements StorageInterface
{
    public $memcached;

    public function __construct($connector = null)
    {
        if (!is_null($connector)) {
            $this->memcached = $connector;
        }
    }

    /**
     * <code>
     *  $connector = new MemcachedConnector()
     *  $connection = $connector->create($servers);.
     *
     *  Cache::make('memcached', function ($memcached) use($connection)
     *  {
     *      $memcached->setConnector($connection);
     *
     *      $memcached->store('foo', 'Foo Bar');
     *  });
     *
     * </code>
     */
    public function setConnector($connector)
    {
        $this->memcached = $connector;
    }

    public function memcached()
    {
        return $this->memcached;
    }

    /**
     * Store the value in the memcached memory (overwrite if key exists).
     *
     * @false string $key
     * @false mix $value
     * @false bool $compress
     * @false int $expire (seconds before item expires)
     *
     * @param     $key
     * @param     $value
     * @param int $minutes
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    public function store($key, $value, $minutes = 10)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException('Invalid key passed to Memcached::'.__FUNCTION__);
        }

        return $this->memcached()->set($key, $value, $minutes * 60);
    }

    /**
     * @param     $key
     * @param     $value
     * @param int $minutes
     *
     * @return mixed
     */
    public function add($key, $value, $minutes = 10)
    {
        return $this->memcached()->add($key, $value, $minutes * 60);
    }

    /**
     * @param     $key
     * @param int $value
     *
     * @return mixed
     */
    public function increment($key, $value = 1)
    {
        return $this->memcached()->increment($key, $value);
    }

    /**
     * @param     $key
     * @param int $value
     *
     * @return mixed
     */
    public function decrement($key, $value = 1)
    {
        return $this->memcached()->decrement($key, $value);
    }

    /**
     * Get data from memory based on its key.
     *
     * @false string $key
     *
     * @param $key
     *
     * @return bool
     */
    public function get($key)
    {
        $data = [];
        $data = $this->memcached()->get($key);

        return ($this->memcached()->getResultCode() == 0) ? $data : null;
    }

    /**
     * Delete values from memory based on its key.
     *
     * @false string $key
     *
     * @param $key
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function destroy($key)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException('Empty key passed to Memcached::'.__FUNCTION__);
        }

        return $this->memcached()->delete($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->memcached()->flush();
    }
}
