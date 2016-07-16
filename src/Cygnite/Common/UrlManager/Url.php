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

use Cygnite\Base\Router\Router;

/**
 * Class Url
 *
 * @package Cygnite\Common\UrlManager
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Url
{
    public static $base;

    private static $router;

    private static $request;

    private $app;

    /**
     * Set application instance
     * Set router and request
     *
     * @param $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;
        $this->setRouter($app['router']);
        $this->setRequest($app['request']);

        return $this;
    }

   /**
     * @param $route
     */
    public function setRouter($route)
    {
        static::$router = $route;
    }

    /**
     * @param $request
     * @return $this
     */
    public function setRequest($request)
    {
        static::$request = $request;

        return $this;
    }

    /**
     * Header Redirect
     *
     * @param string $uri
     * @param string $type
     * @param int $httpResponseCode
     */
    public static function redirectTo($uri = '', $type = 'location', $httpResponseCode = 302)
    {
        $uri = str_replace(['.', '/'], '/', $uri);

        if (!preg_match('#^https?://#i', $uri)) {
            $uri = self::sitePath($uri);
        }

        switch ($type) {
            case 'refresh':
                header("Refresh:0;url=" . $uri);
                break;
            case 'location':
                header("Location: " . $uri, true, $httpResponseCode);
                break;
        }
        exit;
    }

    /**
     * This Function is to get the url sitePath with index.php
     *
     * @access public
     * @false $uri
     * @param $uri
     * @return string
     */
    public static function sitePath($uri)
    {
        $expression = array_filter(explode('/', static::$request->server['REQUEST_URI']));
        $index = (false !== array_search(Router::$indexPage, $expression)) ? Router::$indexPage.'/' : '';

        return Url::getBase() . $index . $uri;
    }

    /**
     * @param $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args = [])
    {
        $instance = Url::make();
        $arguments = ['method' => $method, 'args' => $args, 'instance' => $instance];
        return call_user_func_array([$instance, 'call'], [$arguments]);
    }

    /** Return Url Instance
     *
     * @return static
     */
    public static function make()
    {
        return new static();
    }

    /**
     * Used to get the previous visited url based on current url
     *
     * @access public
     * @return string
     */
    public function referredFrom()
    {
        return isset(static::$request->server["HTTP_REFERER"]) ? static::$request->server["HTTP_REFERER"] : null;
    }

    /**
     * This Function is to get Uri Segment of the url
     *
     * @access public
     * @false  int
     * @param array|int $segment
     * @return string
     */
    public function getSegment($segment = [])
    {
        $segment = (!is_null($segment[0])) ? $segment[0] : 1;
        $uri = $this->getRouter()->getCurrentUri();
        $urlArray = array_filter(explode('/', $uri));
        $indexCount = array_search(Router::$indexPage, $urlArray);

        if ($indexCount == true) {
            $num = $indexCount + $segment;
            return (isset($urlArray[$num]) ? $urlArray[$num] : null);
        }

        return (isset($urlArray[$segment]) ? $urlArray[$segment] : null);
    }

    /**
     * @return instance / null
     */
    public function getRouter()
    {
        return isset(static::$router) ? static::$router : null;
    }

    /**
     * This Function is to encode the url
     *
     * @access public
     * @false  string
     * @param $str
     * @return string
     */
    public function encode($str)
    {
        return urlencode($str);
    }

    /**
     * This Function is to decode the url
     *
     * @access public
     * @false  string
     * @param $str
     * @return string
     */
    public function decode($str)
    {
        return urldecode($str);
    }

    /**
     * @param $arguments
     * @return string
     * @throws \InvalidArgumentException
     */
    private function configureBase($arguments)
    {
        $args = $arguments['args'];
        $match = $arguments['match'];
        $protocol = $this->protocol(); // get the server protocol

        $reflector = new \ReflectionClass(__CLASS__);
        $property = strtolower($match[2]) . $match[3];

        if ($reflector->hasProperty($property)) {
            $property = $reflector->getProperty($property);

            switch ($match[1]) {
                case 'get':
                    return $protocol . static::$request->server->get('HTTP_HOST') . '/' . ltrim($property->getValue(), "/");
                case 'set':
                    return $protocol . static::$request->server->get('HTTP_HOST') . $property->setValue($args[0]);
            }
        }

        throw new \InvalidArgumentException("Url::{$property} doesn't exist");
    }

    /**
     * @return string
     */
    public function protocol()
    {
        // Check if application is running into secure https url
        return (static::$request->isSecure()) ? 'https://' : 'http://';
    }

    /**
     * @param $arguments
     * @return mixed
     */
    private function call($arguments)
    {
        $method = $arguments['method'];
        $args = $arguments['args'];
        $url = $arguments['instance'];

        switch ($method) {
            case 'make':
                return $url;
                break;
            case 'segment':
                return $this->{'get' . ucfirst($method)}($args);
                break;
            default:
                if (preg_match('/^([gs]et)([A-Z])(.*)$/', $method, $match)) {
                    $parameters = ['args' => $args, 'match' => $match];
                    return call_user_func_array([$url, 'configureBase'], [$parameters]);
                }
                break;
        }
    }
}
