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

use Exception;
use Cygnite\Proxy\StaticResolver;
use Cygnite\Cache\StorageInterface;
use Cygnite\Cache\Exceptions\ApcExtensionNotFoundException;

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

    // default life time
    private $lifeTime;

    private $defaultTime = 600; //default time is set 600

    private $is_enable = false;   // is apc enabled

    private $option = false; // flag set false

    /*
    * Constructor function to check availability of apc, throw exception if not available
    *
    */
    public function __construct()
    {
        if (extension_loaded('apc')) {
            $this->is_enable = true;
        } else {
            throw new ApcExtensionNotFoundException("Apc extension not loaded !");
        }
    }

    /*
    * Prevent cloning
    */
    final private function __clone()
    {

    }

    /**
     * @return Apc
     */
    public static function make()
    {
        return new self();
    }

    /*
    * This function is used to set default life time
    * @false $default_lifeTime null
    *@return  boolean
    */
    public function setLifeTime($defaultLifeTime = "")
    {
         $this->lifeTime = ($defaultLifeTime == "" || is_null($defaultLifeTime))
             ? $this->defaultTime : $defaultLifeTime;

         return true;
    }

    /**
    * This function is used to get life time of apc cache
    * @return  boolean
    */

    public function getLifeTime()
    {
        return (!is_null($this->lifeTime)) ? $this->lifeTime : $this->defaultTime;
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
    public function store($key, $value)
    {
        if (is_null($key) || $key == "") {
            throw new \InvalidArgumentException("Empty key passed ".__FUNCTION__);
        }

        if (is_null($value) || $value == "") {
            throw new \InvalidArgumentException("Empty value passed ".__FUNCTION__);
        }

        return (apc_store($key, $value, $this->getLifeTime()))
            ? true : false;
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
        $result = apc_fetch($key, $this->option);
        return ($this->option) ? $result : null;
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
        if (is_null($key) || $key == "") {
            throw new \InvalidArgumentException("Empty key passed ".__FUNCTION__);
        }

        apc_fetch($key, $this->option);

        return ($this->option) ? apc_delete($key) : true;
    }
    }
