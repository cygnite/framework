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

use InvalidArgumentException;
use Cygnite\Base\Router;

/*
 * Url.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Url
{

    public static $base;

    private static $instance = 'instance';

    private static $router;

    public function __construct(Router $route)
    {
        if(is_object($route)) {
            if ($route instanceof Router) {
                $this->setRoute($route);
            }
        }
    }

    /**
     * @param $route
     */
    private function setRoute($route)
    {
        static::$router = $route;
    }

    /**
     * @return instance / null
     */
    public function getRoute()
    {
        return isset(static::$router) && is_object(static::$router) ? static::$router : null;
    }

    /**
     * Header Redirect
     *
     * @access    public
     * @param     string $uri
     * @param    string $type
     * @param     int    $httpResponseCode
     * @internal  false \Cygnite\Helpers\the $string URL
     * @internal  false \Cygnite\Helpers\the $string method: location or redirect
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     * @return    string
     */
    public static function redirectTo($uri = '', $type = 'location', $httpResponseCode = 302)
    {
        $uri = str_replace(array('.', '/'), '/', $uri);

        if (! preg_match('#^https?://#i', $uri)) {
            $uri = self::sitePath($uri);
        }

        switch ($type) {
            case 'refresh':
                header("Refresh:0;url=".$uri);
                break;
            case 'location':
                header("Location: ".$uri, true, $httpResponseCode);
                break;
        }
    }

    /**
    * This Function is to get the previous visited url based on current url
    *
    * @access public
    * @return string
    */
    public function referredFrom()
    {

    }

    /**
     * This Function is to get Uri Segment of the url
     *
     * @access public
     * @false  int
     * @param array|int $segment
     * @return string
     */
    public function getSegment($segment = array())
    {
        $segment = (!is_null($segment[0])) ? $segment[0] : 1;
        $uri = $this->getRoute()->getCurrentUri();
        $urlArray = array_filter(explode('/', $uri));

        $indexCount = array_search('index.php', $urlArray);

        if ($indexCount == true) {
            return @$urlArray[$indexCount+$segment];
        } else {
            return @$urlArray[$segment];
        }
    }


    /**
     * @param $method
     * @param $args
     * @return mixed|string
     * @throws \InvalidArgumentException
     */
    public static function __callStatic($method, $args)
    {
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';

        if (preg_match('/^([gs]et)([A-Z])(.*)$/', $method, $match)) {

            $reflector = new \ReflectionClass(__CLASS__);

            $property = strtolower($match[2]). $match[3];

            if ($reflector->hasProperty($property)) {
                 $property = $reflector->getProperty($property);

                switch ($match[1]) {
                    case 'get':
                        return $protocol.$_SERVER['HTTP_HOST'].'/'.ltrim($property->getValue(), "/");
                    case 'set':
                        return $protocol.$_SERVER['HTTP_HOST'].$property->setValue($args[0]);
                }
            } else {
                    throw new \InvalidArgumentException("Url Property {$property} doesn't exist");
            }
        }
        if ($method == self::$instance) {
            return call_user_func_array(array(new Url($args[0]), $method), array($args));
        }

        if ($method == 'segment') {
            return call_user_func_array(array(new Url(new Router()), 'get'.ucfirst($method)), array($args));
        }
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return $this|mixed
     */
    public function __call($method, $arguments = array())
    {

        if ($method == self::$instance) {
            return $this;
        }
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
        $expression = array_filter(explode('/', $_SERVER['REQUEST_URI']));
        $index = (false !== array_search('index.php', $expression)) ? 'index.php/' : '';

        return  Url::getBase().$index.$uri;
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
}
