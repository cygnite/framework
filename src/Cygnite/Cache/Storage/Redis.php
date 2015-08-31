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
 * Cygnite Redis Cache Wrapper Class
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */

class Redis implements StorageInterface
{
    public $redis;

    public $prefix = 'cygnite:';

    /**
     * @param null $connector
     */
    public function __construct($connector = null)
    {
        if (!is_null($connector)) {
            $this->redis = $connector;
        }
    }

    /**
     * @param $connection
     */
    public function setConnection($connection)
    {
        $this->redis = $connection;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function connection($name = 'default')
    {
        return $this->redis->connection($name);
    }

    /**
     * @return null
     */
    public function redis()
    {
        return $this->redis;
    }

    /**
     * @param     $key
     * @param     $data
     * @param int $minutes
     * @return mixed
     */
    public function store($key, $data, $minutes = 1)
    {
        $data = (!is_numeric($data)) ? serialize($data) : $data;

        return $this->connection()->setex($this->prefix.$key, $minutes * 60, $data);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        $value = (!is_numeric($value)) ? serialize($value) : $value;

        return $this->connection()->set($this->prefix.$key, $value);
    }

    public function get($key)
    {
        $data = $this->connection()->get($this->prefix.$key);

        if (!is_null($data)) {
            return (is_numeric($data)) ? $data : unserialize($data);
        }

        return null;
    }

    /**
     * @param     $key
     * @param int $value
     * @return mixed
     */
    public function increment($key, $value = 1)
    {
        return $this->connection()->incrby($this->prefix.$key, $value);
    }

    /**
     * @param     $key
     * @param int $value
     * @return mixed
     */
    public function decrement($key, $value = 1)
    {
        return $this->connection()->decrby($this->prefix.$key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function destroy($key)
    {
        return $this->connection()->del($this->prefix.$key);
    }

    /**
     * Flush all data from redis storage
     */
    public function flush()
    {
        $this->connection()->flushdb();
    }
}
