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

use Cygnite\Router\Router;
use Cygnite\Container\ContainerAwareInterface;

/**
 * Class Url.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Manager
{
    public static $base;

    protected $router;

    protected $request;

    protected $container;

    private $url;

    public function __construct(ContainerAwareInterface $container)
    {
        $this->container = $container;
        $this->request = $container['request'];
        $this->router = $container['router'];
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
    public function getSegment($segment = 1)
    {
        $segment = (!is_null($segment)) ? $segment : 1;
        $uri = $this->router->getCurrentUri();
        $urlArray = array_filter(explode('/', $uri));
        $indexCount = array_search(Router::$indexPage, $urlArray);

        if ($indexCount == true) {
            $num = $indexCount + $segment;

            return isset($urlArray[$num]) ? $urlArray[$num] : null;
        }

        return isset($urlArray[$segment]) ? $urlArray[$segment] : null;
    }

    /**
     * Header Redirect.
     *
     * @param string $uri
     * @param string $type
     * @param int    $httpResponseCode
     */
    public function redirectTo($uri, $type = 'location', $httpResponseCode = 302)
    {
        $uri = str_replace(['.', '/'], '/', $uri);

        if (!preg_match('#^https?://#i', $uri)) {
            $uri = $this->sitePath($uri);
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
     * @param $uri
     * @return string
     */
    public function sitePath($uri)
    {
        $expression = array_filter(explode('/', $this->request->server['REQUEST_URI']));
        $index = (false !== array_search(Router::$indexPage, $expression)) ? Router::$indexPage.'/' : '';
        
        return $this->getBase().$index.$uri;
    }

    /**
     * Used to get the previous visited url based on current url.
     *
     * @return string
     */
    public function referredFrom()
    {
        return isset($this->request->server['HTTP_REFERER']) ?
            $this->request->server['HTTP_REFERER'] :
            null;
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
     * Return HTTP protocol
     *
     * @return string
     */
    public function protocol()
    {
        // Check if application is running into secure https url
        return ($this->request->isSecure()) ? 'https://' : 'http://';
    }

    /**
     * Return HTTP host name
     *
     * @return string
     */
    public function getHttpHost()
    {
        return $this->request->server->get('HTTP_HOST');
    }

    /**
     * Set Base Url
     *
     * @param $uri
     */
    public function setBase($uri)
    {
        $this->url = $this->protocol().$this->getHttpHost().$uri;
    }

    /**
     * Get Base Url
     *
     * @return mixed
     */
    public function getBase()
    {
        return $this->url;
    }
}
