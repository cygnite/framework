<?php
namespace Cygnite\Cache\Storage;

use Exception;
use Cygnite\Cache\StorageInterface;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *    http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                 : Cache
 * @Filename                : Apc.php
 * @Description             : This driver library is used to store , retrive and destroy data from apc memory.
 *                            Use of this library is to boost up application performance.
 *                            This library required abstract storage class to implement APC Cache.
 * @Author                  : Sanjoy Dey
 * @Copyright               :  Copyright (c) 2013 - 2014,
 * @Link	                :  http://www.cygniteframework.com
 * @Since	                :  Version 1.0
 * @FileSource
 * @Warning                 :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

/**
 * @require Abstract storage class to implement APC Cache
 */
class Apc extends StorageInterface
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
            throw new \Exception("Apc extension not loaded !");
        }
    }

    /*
    * Prevent cloning
    */
    final private function __clone()
    {

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

    private function store($key, $value)
    {

    }
    /**
    * Call the function to save data into apc memory
    * @false name key
    * @false args value to be stored
    */
    public function __call($name, $args)
    {
        if ($name == 'save') {
            return call_user_func_array(array($this, 'save'), $args);
        }

        throw new \Exception("Undefined method called.");
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
    protected function save($key, $value)
    {
        if (is_null($key) || $key == "") {
            throw new \Exception("Empty key passed ".__FUNCTION__);
        }

        if (is_null($value) || $value == "") {
            throw new \Exception("Empty value passed ".__FUNCTION__);
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
    public function fetch($key)
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
            throw new \Exception("Empty key passed ".__FUNCTION__);
        }

        apc_fetch($key, $this->option);

        return ($this->option) ? apc_delete($key) : true;
    }

    /*
    * Destructor function
    */
    public function __destruct()
    {

    }

}
