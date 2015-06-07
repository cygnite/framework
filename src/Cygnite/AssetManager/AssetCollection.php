<?php
namespace Cygnite\AssetManager;

use Closure;
use Cygnite\Common\UrlManager\Url;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class AssetCollection
 *
 * Used to get the Asset object and mange all assets
 * @package Cygnite\AssetManager
 */
class AssetCollection
{
    public static function make(Closure $callback)
    {
        return $callback(new Asset());
    }
}
