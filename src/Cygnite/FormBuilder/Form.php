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
use Cygnite\Http\Requests\Request;
use Cygnite\FormBuilder\Html\Elements;
use Cygnite\Validation\ValidatorInterface;

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

    protected $validator;

    protected $errorClass = 'error';

    protected $errorInputClass = 'error-input';

    public static $elNum = 1;

    protected $entity;

    protected $validArray = [
        'text', 'select', 'textarea', 'custom', 'dateTimeLocal', 'select', 'radio', 'checkbox'
    ];

    protected $validMethods = [
        'textarea', 'select', 'label', 'button', 'custom', 'openTag', 'closeTag', 'dateTimeLocal'
    ];

    protected $request;

    /**
     * Set Http Request object.
     *
     * @param $request
     */
    public function setRequest(Request $request) : FormInterface
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Bind a model or entity object to Form.
     *
     * @param $entity
     * @return FormInterface
     */
    public function bind($entity) : FormInterface
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @param       $method
     * @param array $arguments
     * @throws \Exception
     * @return mixed
     */
    public static function __callStatic($method, $arguments = [])
    {
        if (!method_exists(new static(), $method)) {
            throw new \Exception("Undefined $method method called.");
        }
    }

    /**
     * Get the form builder instance to build form.
     *
     * @param callable $callback
     * @return static
     */
    public static function make(Closure $callback = null) : FormInterface
    {
        if ($callback instanceof Closure) {
            return $callback(new static());
        }

        return new static();
    }

    /**
     * Form open tag.
     *
     * @param       $formName
     * @param array $attributes
     * @return $this
     */
    public function open(string $formName, array $attributes = []) : FormInterface
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
    */
    public function addElement(string $type, string $key, array $array = []) : FormInterface
    {
        $array['type'] = $type;

        if ($type == 'openTag') {
            $key = $key.'_'.mt_rand(1, 2000);
        } else if ($type == 'closeTag') {
            $key = $key.'_'.mt_rand(2000, 4000);
        }

        $this->value[$key] = $array;

        return $this;
    }

    /**
     * Add array of elements.
     *
     * @param array $elements
     * @return $this
     */
    public function addElements(array $elements = []) : FormInterface
    {
        $this->value = array_shift($elements);

        return $this;
    }

    /**
     * Create form elements.
     *
     * @return $this
     */
    public function createForm() : FormInterface
    {
        foreach ($this->value as $key => $val) {

            if (in_array($val['type'], $this->validMethods)) {
                $method = $val['type'];
                unset($val['type']);
                (!method_exists($this, $method)) ?: $this->{$method}($key, $val);
            } else {
                  $this->input($key, $val);
            }

            if (isset($val['type']) && in_array($val['type'], $this->validArray)) {
                $this->setValidationError($key, $val);
            } else if (!isset($val['type']) && in_array($method, $this->validArray)) {
                $this->setValidationError($key, $val);
            }
        }

        return $this;
    }

    /**
     * Create validation error element in the form itself.
     *
     * @param $key
     * @param $val
     */
    protected function setValidationError($key, $val)
    {
        if (!in_array('submit', $val)) {
            // Is $validator is instance of ValidatorInterface and given key has error associated
            // then add a span tag below the input element and display error.
            if ($this->validator instanceof ValidatorInterface && $this->validator->hasError($key)) {
                $this->elements[self::$formHolder[self::$formName]][$key.'.error'] =
                    '<span class="'.$this->errorClass.'">'.$this->validator->getErrors($key).'</span>'.PHP_EOL;
            }
        }
    }

    /**
     * Check if it is valid post.
     *
     * @return bool
     */
    public function isValidRequest() : bool
    {
        return (strtolower($this->request->getMethod()) == strtolower('POST')) ? true : false;
    }

    /**
     * Check if field posted, and post array contain field as key.
     *
     * @param $input
     */
    public function isSubmitted($input)
    {
        return $this->request->postArrayHas($input);
    }

    /**
     * Create form open tag.
     *
     * @param $key
     * @param $val
     */
    protected function form($key, $val)
    {
        $type = null;
        $type = strtolower(__FUNCTION__);

        $this->attributes[self::$formHolder[self::$formName]][$key] =
            "<$type name='".self::$formName."' ".$this->attributes($val).'>'.PHP_EOL;
    }

    /**
     * Dynamically set values for form.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get values.
     *
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * Returns form elements.
     *
     * @return mixed
     */
    public function getForm()
    {
        $elementString = '';

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
     * If you wish to get only html elements.
     */
    public function getHtmlElements() : string
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
     * Returns csrf token.
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
            $this->{$close} = '</form>'.PHP_EOL;
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
