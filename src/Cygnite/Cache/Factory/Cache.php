<?php
namespace Cygnite\Cache\Factory;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Cache
{

    public static $drivers = array(
        'file' => "\\Cygnite\\Cache\\Storage\\File",
        'apc' => "\\Cygnite\\Cache\\Storage\\Apc",
        'memcache' => "\\Cygnite\\Cache\\Storage\\MemCache",
        'radis' => "\\Cygnite\\Cache\\Storage\\Radis",

    );

    /**
     * Factory Method to return appropriate driver instance
     * @param          $cache
     * @param callable $callback
     * @return mixed
    */
    public static function make($cache, \Closure $callback)
    {
        if (array_key_exists($cache, static::$drivers) && $callback instanceof \Closure) {
            return $callback(new static::$drivers[$cache]);
        }
            }
        }
