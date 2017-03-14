<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\UrlManager;

/**
 * Class Url.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Url
{
    public static $urlManager;

    /**
     * Url Constructor
     * @param Manager $urlManager
     */
    public function __construct(Manager $urlManager)
    {
        static::$urlManager = $urlManager;
    }

    /**
     * Call url Manager methods if method doesn't exists
     * in current scope.
     *
     * @param $method
     * @param array $arguments
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $arguments = [])
    {
        if (method_exists(static::$urlManager, $method)){
            return call_user_func_array([static::$urlManager, $method], $arguments);
        }

        throw new \BadMethodCallException("Undefined method Url::$method called.");
    }

    /**
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        if (method_exists(static::$urlManager, $method)){
            return call_user_func_array([static::$urlManager, $method], $arguments);
        }

        throw new \BadMethodCallException("Undefined method Url::$method called.");
    }

    /**
     * Return url segment
     *
     * @param $param
     * @return mixed
     */
    public static function segment($param = 1)
    {
        return static::$urlManager->getSegment($param);
    }

    /**
     * Redirect to given url
     *
     * @param string $uri
     * @param string $type
     * @param int $httpResponseCode
     */
    public function redirectTo($uri = '/', $type = 'location', $httpResponseCode = 302)
    {
        static::$urlManager->redirectTo($uri, $type, $httpResponseCode);
    }
}
