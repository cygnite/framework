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
use Cygnite\Common\ArrayManipulator\ArrayAccessor;
use Cygnite\Common\Input\CookieManager\Cookie;
use Cygnite\Common\Security;
use InvalidArgumentException;

/**
 * Class Input.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Input
{
    public $except;

    private $security;

    public $request = [];

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->request = $this->getRequest();
    }

    /**
     * @param callable $callback
     *
     * @return object
     */
    public static function make(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new static(Security::create()));
        }

        return new static(Security::create());
    }

    /**
     * @param $input
     *
     * @return bool
     */
    public function hasPost($input)
    {
        return  filter_has_var(INPUT_POST, $input) ?
            true :
            false;
    }

    /**
     * @param $key
     *
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
     *
     * @throws \InvalidArgumentException
     *
     * @return bool|null
     */
    public function post($key = null, $value = null)
    {
        if (!is_null($this->except)) {
            $this->skip();
        }

        if (!is_null($key) && !string_has($key, '.') && is_null($value)) {
            $postValue = '';
            $key = $this->security->sanitize($key);
            $postValue = $this->security->sanitize($this->request['post'][$key]);
            $this->request['post'][$key] = $postValue;

            if (array_key_exists($key, $this->request['post'])) {
                return $this->request['post'][$key];
            }

            throw new InvalidArgumentException("Invalid key $key passed to ".__METHOD__);
        }

        /*
         | User can access post array element by passing
         | key as dot separator with index
         | ['user' => ['name' => 'foo']]
         |
         | $input->post('user.name'); // output: foo
         */
        if (!is_null($key) && string_has($key, '.') && is_null($value)) {
            $arr = ArrayAccessor::make($this->request['post']);

            return $arr->toString($this->security->sanitize($key));
        }

        $this->setPostValue($key, $value);

        if (is_null($key)) {
            $postArr = $this->security->sanitize($this->request['post']);

            return (!empty($postArr)) ? $postArr : null;
        }
    }

    public function setPostValue($key, $value)
    {
        if (!is_null($key) && !is_null($value)) {

                //Sets new value for given POST variable.
                 //@param string $key Post variable name
                 //@param mixed $value     New value to be set.
                $this->request['post'][$key] = $value;

            return true;
        }
    }

    public function skip()
    {
        if (string_has($this->except, '.')) {
            $arr = explode('.', $this->except);

            foreach ($arr as $key => $value) {
                unset($this->request['post'][$value]);
            }
        } else {
            unset($this->request['post'][$this->except]);
        }
    }

    /**
     * @param $string
     *
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
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return 'get';
        }

        return false;
    }

    /**
     * @param        $type
     * @param string $request
     */
    public function setRequest($type, $request = 'get')
    {
        $this->request[$type] = $request;
    }

    /**
     * @return array
     */
    public function getRequest()
    {
        return ['get' => $_GET, 'post' => $_POST, 'cookie' => $_COOKIE];
    }

    /**
     * @return mixed
     */
    public function json()
    {
        $data = file_get_contents('php://input');

        return json_decode($data);
    }

    /**
     * Check if ajax request.
     *
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
     */
    public function cookie(Closure $callback = null)
    {
        if ($callback instanceof CookieInterface) {
            return Cookie::create($callback);
        }

        return Cookie::create();
    }
}
