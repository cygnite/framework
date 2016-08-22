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
    public static function make(Closure $callback)
    {
        return $callback(new Asset());
    }
}
