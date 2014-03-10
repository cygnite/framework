<?php
namespace Cygnite\Libraries\Cache\Storage;

use Cygnite\Libraries\Cache\StorageInterface;

if ( ! defined('CF_SYSTEM')) exit('External script access not allowed');

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3  or newer
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
 * @Package               : Cygnite Framework Memcache caching mechanism.
 * @Filename              : Memcache.php
 * @Description           : This file is required abstract storage class to implement Memcache library.
 * @Author                : Sanjoy Dey
 * @Copyright             :  Copyright (c) 2013 - 2014,
 * @Link	              : http://www.cygniteframework.com
 * @Since	              :  Version 1.0
 * @Filesource
 * @Warning               :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */
class CMemCache extends StorageInterface
{
    /**
     * Public variable $isEnabled boolean false by default.
     * is enable set as true if Memcache extension available.
     */
    public $isEnabled = false;

    /* Private variable $memObj default null. Store memcache object */
    private $memObj;

    /* Private variable $host null. set hostname based on user input */
    private $host;

    /* Private variable $port null. set port to connect with memcache based on user input. */
    private $port;


    /*
     * Constructor function to check availability
     * of memcache extension class. Throw error on unavailability
     *
     */
    public function __construct()
    {
        if (!class_exists('Memcache')) {
            throw new \Exception("Memcache extension not available !");
        }
    }

    /**
     * Connect memcache based on its host and port.
     * Connect with default port if hostname and port number not passed
     *
     * @false string $host
     * @false mix $port
     * @param string $host
     * @param string $port
     * @return void
     */
    public function addServer($host = '', $port = '')
    {
        if ($host == '' && $port == '') {
            $this->host = 'localhost';
            $this->port = 11211;
        } else {
            $this->host = $host;
            $this->port = $port;
        }

        if (class_exists('Memcache')) {

            if ($this->memObj == null) {
                 $this->memObj = new \Memcache();

                $this->isEnabled = true;

                if (! $this->memObj->connect($this->host, $this->port)) { // Instead 'localhost' here can be IP
                    $this->memObj = null;
                    $this->isEnabled = true;
                }
            }
        }
    }
    /*
     * Prevent cloning
     */
    final private function __clone()
    {

    }

    /*
     * Private store function
     */
    private function store($key, $value)
    {

    }
    /*
     * Call the function to save data into memcache
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
     * Store the value in the memcache memory (overwrite if key exists)
     *
     * @false string $key
     * @false mix $value
     * @false bool $compress
     * @false int $expire (seconds before item excfres)
     * @param     $key
     * @param     $value
     * @param int $compress
     * @param int $expire_time
     * @throws \Exception
     * @return bool
     */
    protected function save($key, $value, $compress=0, $expire_time=600)
    {
        if (is_null($key) || $key == "") {
            throw new \Exception("Empty key passed ".__FUNCTION__);
        }

        if (is_null($value) || $value == "") {
            throw new \Exception("Empty key passed ".__FUNCTION__);
        }

        //Used MEMCACHE_COMPRESSED to store the item compressed (uses zlib).  $this->life_time
        return $this->memObj->set($key, $value, $compress ? MEMCACHE_COMPRESSED : null, $expire_time);
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
        $data = array();
        $data = $this->memObj->get($key);
        return (false === $data) ? null : $data;
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

        return $this->memObj->delete($key);
    }
    /*
     * Destructor function to unset variables from the memory to boost up performance
     */
    public function __destruct()
    {
        unset($this->memObj);
        unset($this->host);
        unset($this->port);
    }
}
