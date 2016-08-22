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
use Cygnite\Foundation\Application as App;

/**
 * Class Url.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Url
{
    public static $base;
    private static $instance = 'make';
    private static $router;

    /**
     * @param Router $route
     */
    public function __construct(Router $route)
    {
        if (is_object($route)) {
            $this->setRoute($route);
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
     * Header Redirect.
     *
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     *
     * @internal  false \Cygnite\Helpers\the $string URL
     * @internal  false \Cygnite\Helpers\the $string method: location or redirect
     *
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     *
     * @return string
     */
    public static function redirectTo($uri = '', $type = 'location', $httpResponseCode = 302)
    {
        $uri = str_replace(['.', '/'], '/', $uri);

        if (!preg_match('#^https?://#i', $uri)) {
            $uri = self::sitePath($uri);
        }

        switch ($type) {
            case 'refresh':
                header('Refresh:0;url='.$uri);
                break;
            case 'location':
                header('Location: '.$uri, true, $httpResponseCode);
                break;
        }
        exit;
    }

    /**
     * This Function is to get the url sitePath with index.php.
     *
     * @false $uri
     *
     * @param $uri
     *
     * @return string
     */
    public static function sitePath($uri)
    {
        $expression = array_filter(explode('/', $_SERVER['REQUEST_URI']));
        $index = (false !== array_search('index.php', $expression)) ? 'index.php/' : '';

        return self::getBase().$index.$uri;
    }

    /**
     * @param $method
     * @param $args
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed|string
     */
    public static function __callStatic($method, $args = [])
    {
        $arguments = ['method' => $method, 'args' => $args, 'instance' => self::make()];

        return call_user_func_array([self::make(), 'call'], [$arguments]);
    }

    /** Return Url Instance
     * @return static
     */
    public static function make()
    {
        $app = App::instance();

        return new static($app['router']);
    }

    /**
     * Used to get the previous visited url based on current url.
     *
     * @return string
     */
    public function referredFrom()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * This Function is to get Uri Segment of the url.
     *
     * @false  int
     *
     * @param array|int $segment
     *
     * @return string
     */
    public function getSegment($segment = [])
    {
        $segment = (!is_null($segment[0])) ? $segment[0] : 1;
        $uri = $this->getRoute()->getCurrentUri();
        $urlArray = array_filter(explode('/', $uri));
        $indexCount = array_search('index.php', $urlArray);

        if ($indexCount == true) {
            $num = $indexCount + $segment;

            return isset($urlArray[$num]) ? $urlArray[$num] : null;
        } else {
            return isset($urlArray[$segment]) ? $urlArray[$segment] : null;
        }
    }

    /**
     * @return instance / null
     */
    public function getRoute()
    {
        return isset(static::$router) && is_object(static::$router) ? static::$router : null;
    }

    /**
     * This Function is to encode the url.
     *
     * @false  string
     *
     * @param $str
     *
     * @return string
     */
    public function encode($str)
    {
        return urlencode($str);
    }

    /**
     * This Function is to decode the url.
     *
     * @false  string
     *
     * @param $str
     *
     * @return string
     */
    public function decode($str)
    {
        return urldecode($str);
    }

    /**
     * @param $arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function configureBase($arguments)
    {
        $args = $arguments['args'];
        $match = $arguments['match'];
        $protocol = $this->protocol(); // get the server protocol

        $reflector = new \ReflectionClass(__CLASS__);
        $property = strtolower($match[2]).$match[3];

        if ($reflector->hasProperty($property)) {
            $property = $reflector->getProperty($property);

            switch ($match[1]) {
                case 'get':
                    return $protocol.$_SERVER['HTTP_HOST'].'/'.ltrim($property->getValue(), '/');
                case 'set':
                    return $protocol.$_SERVER['HTTP_HOST'].$property->setValue($args[0]);
            }
        } else {
            throw new \InvalidArgumentException("Url::{$property} doesn't exist");
        }
    }

    /**
     * @return string
     */
    public function protocol()
    {
        $protocol = 'http://';

        if ($this->isSecure()) {
            // SSL connection
            $protocol = 'https://';
        }

        return $protocol;
    }

    /**
     * We will check if application is running into secure https url.
     *
     * @return bool
     */
    public function isSecure()
    {
        $scheme = $protocol = '';
        $scheme = (!isset($_SERVER['REQUEST_SCHEME'])) ?: $_SERVER['REQUEST_SCHEME'];
        $protocol = (!isset($_SERVER['SERVER_PROTOCOL'])) ?: $_SERVER['SERVER_PROTOCOL'];

        if (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
            stripos($scheme, 'https') || stripos($protocol, 'https')
        ) {
            // SSL connection
            return true;
        }

        return false;
    }

    /**
     * @param $arguments
     *
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
                return $this->{'get'.ucfirst($method)}($args);
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
