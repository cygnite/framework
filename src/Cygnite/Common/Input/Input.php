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

use Closure;
use Cygnite\Common\Security;
use InvalidArgumentException;
use Cygnite\Common\CookieManager\Cookie;

/**
 * Input.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Input
{
    public $except;

    private $security;

    public $request = [];

    /**
     *
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->request = $this->getRequest();
    }

    /**
     * @param callable $callback
     * @return object
     */
    public static function make(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new Static(new Security()));
        }

        return new Static(new Security());
    }

    /**
     * @param $input
     * @return bool
     */
    public function isPost($input)
    {
        return  filter_has_var(INPUT_POST, $input) ?
            true :
            false;
    }

    /**
     * @param $key
     * @return $this
     */
    public function except($key)
    {
        $this->except = $this->security->sanitize($key);
        return $this;
    }

    /**
     * @param null $key
     * @param null $value
     * @return bool|null
     * @throws \InvalidArgumentException
     */
    public function post($key = null, $value = null)
    {
        if (!is_null($this->except)) {
            unset($this->request['post'][$this->except]);
        }

        $postValue = '';

        if ($key !== null &&
            strpos($key, '.') == false &&
            is_null($value) == true
        ) {
            $key = $this->security->sanitize($key);
            $postValue = $this->security->sanitize($this->request['post'][$key]);
            $this->request['post'][$key] = $postValue;

            if (array_key_exists($key, $this->request['post'])) {
                return $this->request['post'][$key];
            }

            throw new InvalidArgumentException("Invalid $key passed to ".__METHOD__);
        }

        if ($key !== null &&
           strpos($key, '.') == true &&
           is_null($value) == true
        ) {
            $expression = explode('.', $key);
            $firstKey = $this->security->sanitize($expression[0]);
            $secondKey = $this->security->sanitize($expression[1]);

            if (isset($expression[2])) {
                throw new InvalidArgumentException('Post doesn\'t allows more than one key');
            }

            $postValue = $this->security->sanitize($this->request['post'][$firstKey][$secondKey]);
            $this->request['post'][$firstKey][$secondKey] = $postValue;

            return $this->request['post'][$firstKey][$secondKey];
        }

        if (is_null($key) ===false && is_null($value) === false) {
            try {
                 //Sets new value for given POST variable.
                 //@param string $key Post variable name
                 //@param mixed $value     New value to be set.
                $this->request['post'][$key] = $value;
            } catch (InvalidArgumentException $ex) {
                echo $ex->getMessage();
            }

            return true;
        }

        if (is_null($key)) {
            $postArray = $this->security->sanitize($this->request['post']);
            return (!empty($postArray)) ?
                $postArray :
                null;
        }
    }

    /**
     * @param $string
     * @return string
     */
    public function htmlDecode($string)
    {
        return html_entity_decode($string);
    }

    /**
     * @return bool|string
     */
    public function getMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return 'post';
        } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return 'get';
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getRequest()
    {
        return [ 'get' => $_GET, 'post' => $_POST, 'cookie' => $_COOKIE ];
    }

    /**
     * @return mixed
     */
    public function json()
    {
        $data = file_get_contents("php://input");
        return json_decode($data);
    }

    /**
     * Check if ajax request
     * @return bool
     */
    public function isAjax()
    {
        // check is ajax
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            // I'm AJAX!
            return true;
        }

        return false;
    }

    /**
     * Sets or returns the cookie variable value.
     *
     */
    public function cookie(Closure $callback = null)
    {
        if ($callback instanceof CookieInterface) {
            return Cookie::create($callback);
        }

        return Cookie::create();
    }
}