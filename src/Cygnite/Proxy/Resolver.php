<?php
namespace Cygnite\Proxy;

use Cygnite\DependencyInjection\Container;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 * Class Resolver
 *
 * We will make use of Resolver to resolve all static calls.
 *
 * @package Cygnite\Proxy
 */
abstract class Resolver
{
    /**
     * @return Inflector
     */
    private static function getInflection()
    {
        return new Inflector();
    }

    /**
     * We will return proxy objects from container
     *
     * @return Container
     */
    protected static function getContainer()
    {
        return new Container();
    }


    /**
     * All static methods will get resolve by Proxy Resolver
     *
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments = array())
    {
        $accessor = $inflection = $instance = null;
        $accessor = static::getResolver();
        $inflection = self::getInflection();
        $accessor = $inflection->toNamespace($accessor);
        $instance = new $accessor();

        // calling the method directly is faster then call_user_func_array() !
        switch (count($arguments))
        {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($arguments[0]);
            case 2:
                return $instance->$method($arguments[0], $arguments[1]);
            case 3:
                return $instance->$method($arguments[0], $arguments[1], $arguments[2]);
            case 4:
                return $instance->$method($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            default:
                return call_user_func_array(array($instance, $method), $arguments);
        }
    }

    /**
     * Get the object instance for this Facade
     *
     * @since  1.2.1
     */
    public static function getInstance()
    {
        // This Facade doesn't have instance support
        return null;
    }
}
