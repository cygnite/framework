<?php
namespace Cygnite;

use Closure;
use Cygnite\Singleton;

    /**
 *   Cygnite PHP Framework
 *
 *   An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                   :  Packages
 * @Sub Packages              :  
 * @Filename                  :  Cookie
 * @Description               :  Cookie Manager class to manage http cookie.
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @FileSource
 */

class Cookie extends Singleton implements CookieInterface
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

    public static $cookies = array();

    /*
     * Did cookie has been set already ?
     */
    private $setCookie = false;

    //Property to hold security instance.
    private $security;

    /*
     * Constructor of the Cookie.
     * @access private
     * You can not create instance directly.
     * Use Cookie::instance(); to get the object
     *
     * @return void
     */
    protected function __construct()
    {
        //Get the security instance and provide security to cookies
        $this->security = Security::instance(
            function ($instance) {
                return $instance;
            }
        );
    }


    /**
     * @param callable $callback
     * @param array    $request
     * @return object
     */
    public static function getInstance(Closure $callback, $request = array())
    {
        if (!empty($request)) {
            self::$cookies = $request['cookie'];
        } else {
            self::$cookies = $_COOKIE;
        }

        $crypt = null;

        if (is_object($crypt)) {
            self::$encrypt = $crypt;
        }

        return (is_callable($callback)) ?
            $callback(parent::instance()) :
            parent::instance();
    }

    /**
     * Set cookie name
     *
     * @access public
     * @param string $name cookie name
     * @throws \InvalidCookieException
     * @return mixed obj or bool false
     */
    public function setName($name)
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
    public function setValue($value = null)
    {
        if (is_null($value)) {
            throw new \InvalidCookieException("Cookie value cannot be null.");
            return false;
        }

        if (is_array($value)) {
            $value = json_encode($this->security->sanitize($value));
        }

        //$data = $this->encrypt->encode($value);
        $data = $value;

        $length = null;

        $length = (function_exists('mb_strlen')?
            mb_strlen($data) :
            strlen($data));

        if ($length > 4096) {
            throw new \InvalidCookieException('Cookie maximum size exceeds 4kb');
            return false;
        }

        $this->value = $this->security->sanitize($data);
        unset($data);

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
    public function setExpire($expire = 0)
    {
        $var = null;
        $var = substr($expire, 0, 1);

        if (in_array(
            $var,
            array('+','-')
        )
        ) {

            $this->expire = strtotime($expire);

            if ($this->expire === false) {
                throw new \InvalidCookieException(
                    'Cookie->setExpire was passed a string it could not parse - "'.$expire.'"'
                );
            }
            unset($expire);

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
    public function setPath($path = '/')
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

    public function setDomain($domain = null)
    {
        if ($domain !== null) {
            $this->domain = (string) $domain;
        }

        return $this;

    }


    /*
     * Set the cookie status to be secure or not
     *
     * @param bool $bool true/false if secure
     * @return $this
     */

    public function setSecure($bool = false)
    {
        $this->secure = (bool)$bool;

        return $this;
    }


    /*

     * Set the cookie type http only, or not
     * @param bool $bool true/false if http only
     * @return $this
     */
    public function setHttpOnly($bool = false)
    {
        $this->httpOnly = (bool)$bool;

        return $this;
    }


    /*
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

        $value = null;

        if (isset(self::$cookies[$name]) && is_array(self::$cookies[$name])) {
            $value = json_decode(self::$cookies[$name]);
        }

        if (isset(self::$cookies[$name])) {
            $value = $this->security->sanitize(self::$cookies[$name]);

            return  $value;
            //json_decode($this->encrypt->decode($this->cookies[$name])) :
        }

        if (!isset(self::$cookies[$name])) {
            throw new \InvalidCookieException($name.' not found');
            return false;
        }
    }

    /*
     * Set the cookie
     *
     * @return bool
     * @throws \Exceptions  Cookies already set
     */
    public function save()
    {

        if ($this->name && $this->setCookie) {
            throw new \InvalidCookieException(
                'Cookie->setCookie has already been called. Cookies can only set once.'
            );
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
     * @param $cookie
     * @return bool|mixed
     */
    public function has($cookie)
    {
        if (isset(self::$cookies[$cookie]) || $cookie == $this->name) {
            return true;
        }

        return false;
    }

    /**
     * destroy the cookie
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

        return setcookie(
            $name,
            null,
            (time()-1),
            $this->path,
            $this->domain
        );
    }

    public function __destruct()
    {
        $this->setCookie = false;
    }
}