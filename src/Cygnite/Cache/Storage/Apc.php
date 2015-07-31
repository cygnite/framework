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

use Cygnite\Cache\Exceptions\ApcExtensionNotFoundException;
use Cygnite\Cache\StorageInterface;
use Exception;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Cygnite APC Cache Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

/**
 * @require StorageInterface to implement APC Cache
 */
class Apc implements StorageInterface
{
    // life time
    protected $lifeTime;
    protected $defaultTime = 10; //default time is set 10 * 60 = 600 sec    
    protected $option = false; // flag set false
    protected $isApcUEnabled = false;

    /*
    * Constructor function to check availability of apc extension, 
    * throws exception if not available
    *
    */

    public function __construct()
    {
        if (!extension_loaded('apc')) {
            throw new ApcExtensionNotFoundException("Apc extension not loaded !");
        }

        $this->isApcUEnabled = (function_exists('apcu_fetch')) ? true : false;
    }

    /*
    * Prevent cloning
    */

    /**
     * @return Apc
     */
    public static function make()
    {
        return new self();
    }

    /**
     * Store the value in the apc memory
     *
     * @false string $key
     * @false mix $value
     * @param $key
     * @param $value
     * @throws \Exception
     * @return bool
     */
    public function store($key, $value, $minute = null)
    {
        if (is_null($key) || $key == "") {
            throw new \InvalidArgumentException("Key shouldn't be empty");
        }

        $time = (is_null($minute)) ? $this->getLifeTime() : $minute * 60;

        return ($this->isApcUEnabled) ? apcu_store($key, $value, $time): apc_store($key, $value, $time);
    }

    /*
    * This function is used to set default life time
    * @false $default_lifeTime null
    *@return  boolean
    */
    public function setLifeTime($lifeTime = null)
    {
        $this->lifeTime = ((is_null($lifeTime)) ? $this->defaultTime : $lifeTime) * 60;

        return true;
    }

    /**
     * This function is used to get life time of apc cache
     *
     * @return  boolean
     */

    public function getLifeTime()
    {
        return (!is_null($this->lifeTime)) ? $this->lifeTime : $this->defaultTime;
    }

    /**
     * Get data from memory based on its key
     *
     * @false string $key
     * @param $key
     * @return bool
     */
    public function get($key)
    {
        $data = ($this->isApcUEnabled) ? apcu_fetch($key) : apc_fetch($key);

        if ($data == false) {
            return null;
        }

        return $data;
    }

    public function increment($key, $value)
    {
        return ($this->isApcUEnabled) ? apcu_inc($key, $value) : apc_inc($key, $value);
    }

    public function decrement($key, $value)
    {
        return ($this->isApcUEnabled) ? apcu_dec($key, $value) : apc_dec($key, $value);
    }

    /**
     * Delete values from memory based on its key
     *
     * @false string $key
     * @param $key
     * @throws \Exception
     * @return bool
     */
    public function destroy($key)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException("Key shouldn't be empty");
        }

        return ($this->isApcUEnabled) ? apcu_delete($key) : apc_delete($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        ($this->isApcUEnabled) ? apcu_clear_cache() : apc_clear_cache('user');
    }

    final private function __clone()
    {
    }
}
