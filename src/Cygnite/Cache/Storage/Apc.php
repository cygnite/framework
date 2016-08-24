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
use Exception;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite APC Cache Wrapper Class.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Apc implements StorageInterface
{
    protected $apc;

    /*
    * Constructor function to check availability of apc extension,
    * throws exception if not available
    *
    */

    public function __construct(ApcWrapper $apc)
    {
        $this->apc = $apc;
    }

    /**
     * @param callable $callback
     *
     * @return static
     */
    public static function make(callable $callback = null)
    {
        if (is_callable($callback) && !is_null($callback)) {
            return $callback(new static(new ApcWarpper()));
        }

        return new static(new ApcWarpper());
    }

    /**
     * Store item into apc memory.
     *
     * @param      $key
     * @param      $value
     * @param null $minute
     *
     * @return mixed
     */
    public function store($key, $value, $minute = null)
    {
        return $this->apc->store($key, $value, $minute = null);
    }

    /**
     * This function is used to set default life time.
     *
     * @param null $lifeTime
     *
     * @return mixed
     */
    public function setLifeTime($lifeTime = null)
    {
        return $this->apc->setLifeTime($lifeTime);
    }

    /**
     * This function is used to get life time of apc cache.
     *
     * @return bool
     */
    public function getLifeTime()
    {
        return $this->apc->getLifeTime();
    }

    /**
     * Get data from memory based on its key.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->apc->get($key);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function increment($key, $value)
    {
        return $this->apc->increment($key, $value);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function decrement($key, $value)
    {
        return $this->apc->decrement($key, $value);
    }

    /**
     * Delete values from memory based on its key.
     *
     * @param $key
     *
     * @return mixed
     */
    public function destroy($key)
    {
        return $this->apc->destroy($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        return $this->apc->flush();
    }
}
