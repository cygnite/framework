<?php
namespace Cygnite\Cache\Factory;

use Cygnite\Helpers\Config;
use Predis\Client as RedisClient;
use Cygnite\Cache\Storage\ApcWarpper;
use Cygnite\Cache\Storage\RedisConnector;
use Cygnite\Cache\Storage\MemcachedConnector;

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
     * @throws RuntimeException
     * @return mixed
     */
    public static function make($cache, \Closure $callback)
    {
        // Return Closure callback
        if (array_key_exists($cache, static::$drivers)) {
            return static::getCacheDriver($callback, $cache);
        }

        throw new RuntimeException("Cache driver not found!");
    }

    /**
     * @param $callback
     * @param $cache
     * @return mixed
     */
    public static function getCacheDriver($callback, $cache)
    {
        switch ($cache) {
            case 'apc':
                return $callback(new static::$drivers[$cache](new ApcWarpper()));
                break;
            case 'memcached':
                $memcached = static::getMemcahcedDriver();
                return $callback(new static::$drivers[$cache]($memcached));

                break;
            case 'redis':
                $redis = static::getRedisDriver();
                return $callback(new static::$drivers[$cache]($redis));

                break;
            default:
                return $callback(new static::$drivers[$cache]());
                break;
        }
    }

    /**
     * @return null|void
     */
    private static function getMemcahcedDriver()
    {
        $config = Config::get('global.config', 'cache');

        $memcached = null;
        if ($config['memcached']['autoconnnect']) {
            $uniqueId = $config['memcached']['uniqueId'];
            $memCachedInstance = (!is_null($uniqueId)) ? new Memcached($uniqueId) : new Memcached();

            $memcached = (new MemcachedConnector($memCachedInstance))->create($config['memcached']['servers']);
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
            $redis = new RedisConnector(new RedisClient(), $config['redis']);
        }

        return $redis;
    }
}
