<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\FormBuilder;

use Closure;
use Cygnite\Common\Input;
use Cygnite\FormBuilder\Html\Elements;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Form.
 *
 * Build your form on the fly.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */

class Form extends Elements implements FormInterface
{
    public static $formHolder = [];

    public static $formName;

    public static $formOpen;

    protected $attributes = [];

    protected $value = [];

    protected $element = [];

    private $validArray = ['text', 'button', 'select', 'textarea'];

    public $validator;

    protected $errorClass = 'error';

    public static $elNum = 1;

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments = [])
    {
        if (!method_exists(new static, $method)) {
            throw new \Exception("Undefined $method method called.");
        }
    }

    /**
     * Get the form builder instance to build form
     *
     * @param callable $callback
     * @return static
     */
    public static function make(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new static);
        }

        return new static;
    }

    /**
     * Alias method of make
     *
     * @param callable $callback
     * @return callable
     */
    public static function instance(Closure $callback = null)
    {
        return static::make($callback);
    }

    /**
     * Form open tag
     *
     * @param       $formName
     * @param array $attributes
     * @return $this
     */
    public function open($formName, $attributes = [])
    {
        self::$formName = $formName;
        self::$formHolder[$formName] = $formName;

        self::$formOpen = true;
        $this->form($formName, $attributes);

        return $this;
    }

   /*
    * Add form elements
    *
    * @param  $key
    * @param  $rule set up your validation rule
    * @return $this
    *
    */
    public function addElement($type, $key, $array = [])
    {
        $array['type'] = $type;

        if ($type == 'openTag') {
            $key = $key.'_'.mt_rand(1, 2000);
        }

        if ($type == 'closeTag') {
            $key = $key.'_'.mt_rand(2000, 4000);
        }

        $this->value[$key] = $array;

        return $this;
    }

    /**
     * @param array $elements
     * @return $this
     */
    public function addElements($elements = [])
    {
        $this->value = array_shift($elements);

        return $this;
    }

    /**
     * @return $this
     */
    public function createForm()
    {
        foreach ($this->value as $key => $val) {
            switch ($val['type']) {
                case 'textarea':
                    unset($val['type']);
                    $this->textarea($key, $val);
                    break;
                case 'select':
                    unset($val['type']);
                    $this->select($key, $val);
                    break;
                case 'label':
                    unset($val['type']);
                    $this->label($key, $val);
                    break;
                case 'button':
                    unset($val['type']);
                    $this->button($key, $val);
                    break;
                case 'custom':
                    unset($val['type']);
                    $this->custom($key, $val);
                    break;
                case 'openTag':
                    unset($val['type']);
                    $this->openTag($key, $val);
                    break;
                case 'closeTag':
                    unset($val['type']);
                    $this->closeTag($key);
                    break;
                default:
                    $this->input($key, $val);
                    break;
            }

            if (isset($val['type']) && in_array($val['type'], $this->validArray)) {
                if (!in_array('submit', $val)) {
                    if (is_object($this->validator) && isset($this->validator->errors[$key.'.error'])) {
                        $this->elements[self::$formHolder[self::$formName]][$key.'.error'] =
                            '<span class="'.$this->errorClass.'">'.$this->validator->errors[$key.'.error'].'</span>'.PHP_EOL;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidRequest()
    {
        return ($this->getMethod() == 'post') ? true : false;
    }

    /**
     * @param $key
     * @param $val
     */
    protected function form($key, $val)
    {
        $type = null;
        $type = strtolower(__FUNCTION__);

        $this->attributes[self::$formHolder[self::$formName]][$key] =
            "<$type name='".self::$formName."' ".$this->attributes($val).">".PHP_EOL;
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param $key
     * @return null
     */
    public function __get($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        $elementString = "";

        if (isset($this->attributes[self::$formHolder[self::$formName]])) {
            $elementString .= $this->attributes[self::$formHolder[self::$formName]][self::$formName];
        }

        $elementString .= $this->getHtmlElements();

        $close = null;
        $close = self::$formHolder[self::$formName].'_close';

        if (isset($this->attributes[$close])) {
            $elementString .= $this->attributes[$close];
        }

        return $this->element[self::$formHolder[self::$formName]] = $elementString;
    }

    /**
     * If you wish to get only html elements
     */
    public function getHtmlElements()
    {
        $elementString = '';
        /*
         | Build a form and store as string
         */
        foreach ($this->elements[self::$formHolder[self::$formName]] as $key => $val) {
            $elementString .= $val;
        }

        return $elementString;
    }

    /**
     * We will get csrf token
     *
     * @return string
     */
    public function csrfToken()
    {
        return csrf_token();
    }

    //Error occured while using this method
    //Have to work on this.
    public function __toString()
    {
        return $this->getForm();
    }

    /**
     * @return $this
     */
    public function close()
    {
        if (self::$formOpen) {
            $close = trim(self::$formHolder[self::$formName].'_close');
            $this->{$close} = "</form>".PHP_EOL;
        }

        return $this;
    }

    public function flush()
    {
        $this->__destruct();
    }

    public function __destruct()
    {
        unset($this->elements);
        unset($this->element);
    }
}
