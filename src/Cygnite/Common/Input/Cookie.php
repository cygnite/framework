<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\Input;

use Cygnite\Common\Input\CookieManager\CookieManager;
use Cygnite\Common\Input\CookieManager\CookieInterface;

/**
 * Class Cookie
 *
 * @package Cygnite\Common\Input
 */
class Cookie implements CookieInterface
{
    public static $cookie;

    /**
     * Create Cookie manager and store or access cookie
     * information
     *
     */
    public function __construct()
    {
        static::$cookie = CookieManager::create();
    }

    /**
     * Create Cookie Instance and return to user
     *
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Set cookie name
     *
     * @access public
     * @param string $name cookie name
     * @throws \InvalidCookieException
     * @return mixed obj or bool false
     */
    public static function name($name)
    {
        return static::$cookie->name($name);
    }

    /**
     * Set cookie value
     *
     * @access   public
     * @param string $value cookie value
     * @throws \InvalidCookieException
     * @internal param bool $encrypt
     * @return bool whether the string was a string
     */
    public function value($value = null)
    {
        return static::$cookie->name($value);
    }

    /**
     * Set cookie expire time
     *
     * @access   public
     * @param int $expire
     * @throws \InvalidCookieException
     * @internal param string $time +1 day, etc.
     * @return bool whether the string was a string
     */
    public function expire($expire = 0)
    {
        return static::$cookie->name($expire);
    }

    /**
     * Set cookie path
     * @param string $path The cookie path
     * @return $this
     */
    public function path($path = '/')
    {
        return static::$cookie->path($path);
    }


    /**
     * Set the cookie domain
     * @access public
     * @param string $domain The cookie path
     * @return $this
     */

    public function domain($domain = null)
    {
        return static::$cookie->domain($domain);
    }

    /**
     * Set the cookie status to be secure or not
     *
     * @param bool $bool true/false if secure
     * @return $this
     */

    public function secure($bool = false)
    {
        return static::$cookie->secure($bool);
    }


    /**
     * Set the cookie type http only, or not
     * @param bool $bool true/false if http only
     * @return $this
     */
    public function httpOnly($bool = false)
    {
        return static::$cookie->httpOnly($bool);
    }


    /**
     * Get a cookie's value
     *
     * @param string $name The cookie name
     * @return mixed string /bool - The value of the cookie name
     */
    public function get($name = null)
    {
        return static::$cookie->get($name);
    }

    /**
     * Set the cookie
     *
     * @return bool
     * @throws \Exceptions  Cookies already set
     */
    public function store()
    {
        return static::$cookie->save();
    }

    /**
     * Check cookie existance
     *
     * @param $cookie
     * @return bool|mixed
     */
    public function has($cookie)
    {
        return static::$cookie->has($cookie);
    }

    /**
     * Destroy the cookie
     *
     * @access   public
     * @param null $name
     * @internal param string $cookieName to kill
     * @return bool true/false
     */
    public function destroy($name = null)
    {
        return static::$cookie->destroy($name);
    }
}
