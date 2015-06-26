<?php
namespace Cygnite\Cache\Factory;

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
                return $callback(new static::$drivers[$cache]);
            }
        }

        // Return instance of the Cache Driver
        return isset(static::$drivers[$cache]) ? new static::$drivers[$cache] : null;
    }
}
