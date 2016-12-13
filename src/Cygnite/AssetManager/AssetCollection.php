<?php

namespace Cygnite\AssetManager;

use Closure;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class AssetCollection.
 *
 * Used to get the Asset object and mange all assets
 */
class AssetCollection
{
    /**
     * Make Asset collection.
     *
     * @param Closure $callback
     * @return mixed
     */
    public static function make(Closure $callback)
    {
        return $callback(new Asset());
    }

    /**
     * Register Asset  into Asset object and returns
     * Asset object.
     *
     * @param $class
     * @param Closure $callback
     * @return mixed
     */
    public static function create($class)
    {
        (new $class($a = new Asset()))->register();

        return $a;
    }
}
