<?php
namespace Cygnite\Cache\Factory;

use Cygnite\Helpers\Config;
use Cygnite\Cache\Storage\MemcachedConnector;
use Cygnite\Cache\Storage\RedisConnector;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Cache
{
    // Cache Drivers
    public static $drivers = [
        'file' => "\\Cygnite\\Cache\\Storage\\File",
        'apc' => "\\Cygnite\\Cache\\Storage\\Apc",
        'memcache' => "\\Cygnite\\Cache\\Storage\\MemCache",
        'memcached' => "\\Cygnite\\Cache\\Storage\\Memcached",
        'redis' => "\\Cygnite\\Cache\\Storage\\Redis"
    ];

    /**
     * Factory Method to return appropriate driver instance
     *
     * @param          $cache
     * @param callable $callback
     * @return mixed
     */
    public static function make($cache, \Closure $callback = null)
    {
        // Check if $callback is instance of Closure we return callback
        if (!is_null($callback) && $callback instanceof \Closure) {
            if (array_key_exists($cache, static::$drivers)) {
                return static::getCacheDriver($callback, $cache);
            }
        }

        // Return instance of the Cache Driver
        return isset(static::$drivers[$cache]) ? new static::$drivers[$cache] : null;
    }

    /**
     * @param $callback
     * @param $cache
     * @return mixed
     */
    public static function getCacheDriver($callback, $cache)
    {
        if ($cache == 'memcached') {
            $memcached = static::getMemcahcedDriver();

            return $callback(new static::$drivers[$cache]($memcached));
        }

        if ($cache == 'redis') {
            $redis = static::getRedisDriver();

            return $callback(new static::$drivers[$cache]($redis));
        }

        return $callback(new static::$drivers[$cache]());
    }

    /**
     * @return null|void
     */
    private static function getMemcahcedDriver()
    {
        $config = Config::get('global.config', 'cache');

        $memcached = null;
        if ($config['memcached']['autoconnnect']) {
            $memcached = (new MemcachedConnector())->create($config['memcached']['servers']);
        }

        return $memcached;
    }

    /**
     * @return RedisConnector|null
     */
    private static function getRedisDriver()
    {
        $config = Config::get('global.config', 'cache');

        $redis = null;
        if (isset($config['redis'])) {
            $redis = new RedisConnector($config['redis']);
        }

        return $redis;
    }
}
