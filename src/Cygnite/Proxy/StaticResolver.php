<?php
namespace Cygnite\Proxy;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

abstract class StaticResolver
{
    public static $cached = [];

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

        /*-------------------------------------------
         |  If instance method called statically we will return
         |  the child class object
         */
        if ($method == 'instance') {
            self::$cached[$class] = new $class();
            return new $class;
        }

        /**
         * Access all your protected method directly using facade
         * and return value
         */
        return self::$cached[$class] = call_user_func_array([new $class(), $method], $arguments);
    }
}
