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

use Predis\Client as RedisClient;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class RedisConnector.
 *
 * @source https://github.com/nrk/predis
 */
class RedisConnector
{
    protected $redisClient;

    public $config = [];

    public $connections = [];

    /**
     * @param array $config
     */
    public function __construct($client, array $config)
    {
        $this->redisClient = $client;
        $this->config = $config;

        if ($config['connection'] !== 'deafult' && isset($config['connection']['servers'])) {
            $this->connect($config['connection']['servers']);
        } else {
            $this->connectDefault();
        }
    }

    /**
     * @param array $servers
     *
     * @return array
     */
    public function connect(array $servers)
    {
        $class = $this->redisClient;
        $options = isset($servers['options']) ? (array) $servers['options'] : [];

        foreach ($servers as $key => $server) {
            $this->connections[$key] = new $class($server, $options);
        }

        return $this->connections;
    }

    /**
     * @return RedisClient
     */
    public function getRedis()
    {
        return $this->redisClient;
    }

    /**
     * @return RedisClient
     */
    public function connectDefault()
    {
        $class = $this->redisClient;

        return $this->connections['default'] = new $class();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function connection($key = 'default')
    {
        return $this->connections[$key ?: 'default'];
    }

    /**
     * Dynamically call methods of Redis.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, array $arguments = [])
    {
        return call_user_func_array([$this->connection(), $method], $arguments);
    }
}
