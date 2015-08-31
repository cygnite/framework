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
 * Class RedisConnector
 *
 * @package Cygnite\Cache\Storage
 * @source https://github.com/nrk/predis
 */
class RedisConnector
{
    public $config = [];

    public $connections = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if ($config['connection'] !== 'deafult' && isset($config['connection']['servers'])) {
            $this->connect($config['connection']['servers']);
        }

        $this->connectDefault();
    }

    /**
     * @param array $servers
     * @return array
     */
    public function connect(array $servers)
    {
        $options = isset($servers['options']) ? (array) $servers['options'] : [];

        foreach ($servers as $key => $server) {
            $this->connections[$key] = new RedisClient($server, $options);
        }

        return $this->connections;
    }

    /**
     * @return RedisClient
     */
    public function connectDefault()
    {
        return $this->connections['default'] = new RedisClient();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function connection($key = 'default')
    {
        return $this->connections[$key ?: 'default'];
    }

    /**
     * Dynamically call methods of Redis.
     *
     * @param  string  $method
     * @param  array   $arguments
     * @return mixed
     */
    public function __call($method, array $arguments = [])
    {
        return call_user_func_array(array($this->connection(), $method), $arguments);
    }
}
