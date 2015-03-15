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

if ( ! defined('CF_SYSTEM')) exit('External script access not allowed');

/**
 * Cygnite Memcache Cache Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class MemCache implements StorageInterface
{
    /**
     * Public variable $isEnabled boolean false by default.
     * is enable set as true if Memcache extension available.
     */
    public $isEnabled = false;

    /* Private variable $memory default null. Store memcache object */
    private $memory;

    /* Private variable $host null. set hostname based on user input */
    private $host;

    /* Private variable $port null. set port to connect with memcache based on user input. */
    private $port;


    /*
     * Constructor function to check availability
     * of Memcache extension class. Throw error on unavailability
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
    public function create($host = '', $port = '')
    {
        if ($host == '' && $port == '') {
            $this->host = 'localhost';
            $this->port = 11211;
        } else {
            $this->host = $host;
            $this->port = $port;
        }

        if (class_exists('Memcache')) {

            if ($this->memory == null) {
                 $this->memory = new \Memcache();

                $this->isEnabled = true;

                if (! $this->memory->connect($this->host, $this->port)) { // Instead 'localhost' here can be IP
                    $this->memory = null;
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
    public function store($key, $value, $compress=0, $expire_time=600)
    {
        if (is_null($key) || $key == "") {
            throw new \InvalidArgumentException("Invalid key passed MemCache::".__FUNCTION__);
        }

        if (is_null($value) || $value == "") {
            throw new \InvalidArgumentException("Empty value passed MemCache::".__FUNCTION__);
        }

        //Used MEMCACHE_COMPRESSED to store the item compressed (uses zlib).  $this->life_time
        return $this->memory->set($key, $value, $compress ? MEMCACHE_COMPRESSED : null, $expire_time);
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
        $data = array();
        $data = $this->memory->get($key);
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
            throw new \InvalidArgumentException("Empty key passed to MemCache::".__FUNCTION__);
        }

        return $this->memory->delete($key);
    }
    /*
     * Destructor function to unset variables from the memory to boost up performance
     */
    public function __destruct()
    {
        unset($this->memory);
        unset($this->host);
        unset($this->port);
    }
}
