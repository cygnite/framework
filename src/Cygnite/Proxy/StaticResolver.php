<?php

namespace Cygnite\Proxy;

use Cygnite\Foundation\Application;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Resolver.
 *
 * We will make use of Resolver to resolve all static calls.
 */
abstract class StaticResolver
{
    public static $cached = [];

    protected function getResolver()
    {
        $parts = [];
        $parts = explode('\\', __CLASS__);

        return end($parts);
    }

    /**
     * We will return proxy objects from container.
     *
     * @return Container
     */
    public static function app()
    {
        return Application::instance();
    }

    public static function __callStatic($method, $arguments = [])
    {
        $class = '\\'.get_called_class();

        /*-------------------------------------------
         |  We will check is instance of the class already exists
         |  If object is cached then we will simply return it
         */
        if (isset(self::$cached[$class]) && is_object(self::$cached[$class])) {
            return self::$cached[$class];
        }

        /*
         * Access all your protected method directly using facade
         * and return value
         */
        // calling the method directly is faster then call_user_func_[] !
        switch (count($arguments)) {
            case 0:
                return (new $class())->$method();
            case 1:
                return (new $class())->$method($arguments[0]);
            case 2:
                return (new $class())->$method($arguments[0], $arguments[1]);
            case 3:
                return (new $class())->$method($arguments[0], $arguments[1], $arguments[2]);
            case 4:
                return (new $class())->$method($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            default:
                return call_user_func_array([new $class(), $method], $arguments);
        }
    }
}
