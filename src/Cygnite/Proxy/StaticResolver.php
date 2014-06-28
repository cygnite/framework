<?php
namespace Cygnite\Proxy;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

abstract class StaticResolver
{
    public static function __callStatic($method, $arguments = array())
    {
        $class = '\\'.get_called_class();

        /**-------------------------------------------
         *  If instance method called statically we will return
         *  the child class object
         */
        if ($method == 'instance') {
            return new $class;
        }

        /**
         * Access all your protected method directly using facade
         * and return value
         */
        return call_user_func_array(array(new $class, $method), $arguments);
    }
}
