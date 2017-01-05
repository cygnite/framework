<?php

namespace Cygnite\AssetManager;

use Closure;
use Cygnite\Container\ContainerAwareInterface;

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
    protected $asset;

    protected static $inatance;

    /**
     * AssetCollection constructor.
     * @param Asset $asset
     */
    protected function __construct(Asset $asset)
    {
        static::$inatance = $asset;
    }

    /**
     * @return mixed
     */
    public function asset()
    {
        return static::$inatance;
    }


    /**
     * Create a Asset collection object return callback.
     *
     * @param ContainerAwareInterface $container
     * @param callable|null $callback
     * @return Closure
     */
    public static function make(ContainerAwareInterface $container, Closure $callback = null)
    {
        $collection = new AssetCollection(new Asset($container));

        if (is_null($callback)) {
            return $collection->asset();
        }

        return $callback($collection);
    }

    /**
     * Register Asset  into Asset object and returns
     * Asset object.
     *
     * @param $class
     * @param Closure $callback
     * @return mixed
     */
    public static function create($class, ContainerAwareInterface $container) : Asset
    {
        (new $class($a = new Asset($container)))->register();

        return static::$asset = $a;
    }
}
