<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\Input\CookieManager;

use Closure;
use Cygnite\Common\Security;

/**
 * Class Cookie
 *
 * @package Cygnite\Common\Input\CookieManager
 */

class Cookie implements CookieInterface
{
    /**
     * Cookie attributes
     */
    private $name;

    private $value;

    private $expire;

    private $path;

    private $domain;

    private $secure = false;

    private $httpOnly = false;

    public static $cookies = [];

    /*
     * Cookie set already ?
     */
    private $setCookie = false;

    //Property to hold security instance.
    private $security;

    /**
     * Cookie class Constructor.
     *
     * @param Security $security
     * @param array    $request
     */
    public function __construct(Security $security, $request = [])
    {
        //Get the security instance and provide security to cookies
        $this->security = $security;

        if (!empty($request)) {
            static::$cookies = $request['cookie'];
        } else {
            static::$cookies = $_COOKIE;
        }
    }


    /**
     * Create Cookie Instance and return to user
     *
     * @param callable $callback
     * @param array    $request
     * @return object
     */
    public static function create(Closure $callback = null, $request = [])
    {
        return (is_callable($callback)) ?
            $callback(new static(Security::create(), $request)) :
            new static(Security::create(), $request);
    }

    /**
     * Set cookie name
     *
     * @access public
     * @param string $name cookie name
     * @throws \InvalidCookieException
     * @return mixed obj or bool false
     */
    public function name($name)
    {
        if (is_null($name)) {
            throw new \InvalidCookieException("Cookie name cannot be null");

            return false;
        }

        $this->name = (string) $this->security->sanitize($name);

        return $this;
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
        if (is_null($value)) {
            throw new \InvalidCookieException("Cookie value cannot be null.");
        }

        if (is_array($value)) {
            $value = json_encode($this->security->sanitize($value));
        }

        $length = (function_exists('mb_strlen')? mb_strlen($value) : strlen($value));

        if ($length > 4096) {
            throw new \InvalidCookieException('Cookie maximum size exceeds 4kb');
            return false;
        }

        $this->value = $this->security->sanitize($value);

        return $this;
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
        $var = null;
        $var = substr($expire, 0, 1);

        if (in_array($var, ['+', '-'])) {
            $this->expire = strtotime($expire);

            if ($this->expire === false) {
                throw new \InvalidCookieException(
                    'Cookie->setExpire was passed a string it could not parse - "'.$expire.'"'
                );
            }

            return $this;
        }

        $this->expire = 0;

        return $this;
    }

    /**
     * Set cookie path
     * @param string $path The cookie path
     * @return $this
     */
    public function path($path = '/')
    {
        $this->path = (string) $path;

        return $this;
    }


    /**
     * Set the cookie domain
     * @access public
     * @param string $domain The cookie path
     * @return $this
     */

    public function domain($domain = null)
    {
        if ($domain !== null) {
            $this->domain = (string) $domain;
        }

        return $this;
    }

    /**
     * Set the cookie status to be secure or not
     *
     * @param bool $bool true/false if secure
     * @return $this
     */

    public function secure($bool = false)
    {
        $this->secure = (bool)$bool;

        return $this;
    }


    /**
     * Set the cookie type http only, or not
     * @param bool $bool true/false if http only
     * @return $this
     */
    public function httpOnly($bool = false)
    {
        $this->httpOnly = (bool)$bool;

        return $this;
    }


    /**
     * Get a cookie's value
     *
     * @param string $name The cookie name
     * @return mixed string /bool - The value of the cookie name
     */
    public function get($name = null)
    {
        if (is_null($name)) {
            $name = $this->security->sanitize($this->name);
        }

        $name = $this->security->sanitize($name);

        if (!isset(static::$cookies[$name])) {
            throw new InvalidCookieException("Cookie ".$name.' not found');
        }

        if (isset(static::$cookies[$name])) {
            if (is_array(static::$cookies[$name])) {
                return json_decode(static::$cookies[$name]);
            }

            return $this->security->sanitize(static::$cookies[$name]);
        }
    }

    /**
     * Set the cookie
     *
     * @return bool
     * @throws \Exceptions  Cookies already set
     */
    public function store()
    {
        if ($this->name && $this->setCookie == true) {
            throw new InvalidCookieException('Cookies can only set once.');
        }

        $bool = setcookie(
            $this->name,
            $this->value,
            $this->expire,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly
        );

        if ($bool == true) {
            $this->setCookie = true;
        }

        return $bool;
    }

    /**
     * Check cookie existance
     *
     * @param $cookie
     * @return bool|mixed
     */
    public function has($cookie)
    {
        if (isset(static::$cookies[$cookie]) || $cookie == $this->name) {
            return true;
        }

        return false;
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
        if (is_null($name)) {
            $name = $this->name;
        }

        return setcookie($name, null, (time()-1), $this->path, $this->domain);
    }

    public function __destruct()
    {
        $this->setCookie = false;
    }
}
