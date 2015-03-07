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

class Form implements FormInterface
{

    private static $formHolder = array();

    public static $formName;

    public static $formOpen;

    protected $attributes = array();

    protected  $elements = array();

    protected $value = array();

    protected  $element = array();

    private static $object;

    private $validArray = array('text', 'button', 'select', 'textarea');

    public $validator;

    protected $errorClass = 'error';

    private static $validMethod = array('make', 'instance');

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments = array())
    {
        // Check for valid function name
        if (in_array($method, static::$validMethod)) {
            if (empty($arguments)) {
                return call_user_func_array(array(new self,'getInstance'), array());
            } elseif ($arguments[0] instanceof Closure) {
                return call_user_func_array(array(new self,'getInstance'), $arguments);
        }
        }

        throw new \Exception("Undefined $method method called.");
    }

    /**
     * Get the form builder instance to build form
     *
     * @param callable $callback
     * @return Form
     */
    protected function getInstance(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new self);
        }

        return new self;
    }

    /**
     * Form open tag
     *
     * @param       $formName
     * @param array $attributes
     * @return $this
     */
    public function open($formName, $attributes = array())
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
    public function addElement($type, $key, $values = array())
    {
        $values['type'] = $type;
        $this->value[$key] = $values;

        return $this;
    }

    public function withHtml()
    {
        //$this->value[$key] = $values;
        return $this;
    }

    public function setValidation()
    {

    }

    /**
     * @param array $elements
     * @return $this
     */
    public function addElements($elements = array())
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
                default:
                    $this->input($key, $val);
                    break;
            }

            if (isset($val['type']) && in_array($val['type'], $this->validArray)) {

                if (!in_array('submit', $val)) {

                    if (is_object($this->validator) && isset($this->validator->errors[$key.'_error'])) {
                        $this->elements[self::$formHolder[self::$formName]][$key.'_error'] =
                            '<span class="'.$this->errorClass.'">'.$this->validator->errors[$key.'_error'].'</span>'.PHP_EOL;
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
    private function form($key, $val)
    {
        $type = null;
        $type = strtolower(__FUNCTION__);

        $this->attributes[self::$formHolder[self::$formName]][$key] =
            "<$type name='".self::$formName."' ".$this->attributes($val).">".PHP_EOL;

    }

    /**
     * @param $key
     * @param $val
     */
    private function input($key, $val)
    {
        $type = null;
        $type = strtolower(__FUNCTION__);

        $this->elements[self::$formHolder[self::$formName]][$key] =
            "<$type name='".$key."' ".$this->attributes($val)." />".PHP_EOL;

    }

    /**
     * @param $key
     * @param $val
     */
    private function label($key, $val)
    {
        $type = null;
        $type = strtolower(__FUNCTION__);

        $this->elements[self::$formHolder[self::$formName]][$key] =
            "<$type for='".$key."' ".$this->attributes($val).">".$key."</$type>".PHP_EOL;
    }

    /**
     * @param $key
     * @param $val
     */
    private function textarea($key, $val)
    {
        $value = '';
        $value = $val['value'];
        $type = strtolower(__FUNCTION__);
        unset($val['value']);
        $this->elements[self::$formHolder[self::$formName]][$key] =
            "<".$type." name='".$key."'".$this->attributes($val)." >".$value."</".$type.">".PHP_EOL;
    }

    /**
     * @param $key
     * @param $values
     */
    private function select($key, $values)
    {
        $select = $selectValue = '';
        $attributes = array();

        $selectOptions = $values['options'];
        $selected = $values['selected'];
        unset($values['options']);
        unset($values['selected']);
        $attributes = $values;

        $select .= "<".strtolower(__FUNCTION__)." name='".$key."' ".$this->attributes($attributes).">".PHP_EOL;

        foreach ($selectOptions as $key => $value) {
            $selectValue = ($selected == $key) ? 'selected="selected"' : '';
            $select .= "<option $selectValue value='".$key."'>".$value."</option>".PHP_EOL;
        }

        $select .= '</'.strtolower(__FUNCTION__).'>'.PHP_EOL;

        $this->elements[self::$formHolder[self::$formName]][$key] = $select;
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

        foreach ($this->elements[self::$formHolder[self::$formName]] as $key => $val) {
            $elementString .= $val;

        }

        $close = "";
        $close = self::$formHolder[self::$formName].'_close';

        if (isset($this->attributes[$close])) {
            $elementString .= $this->attributes[$close];
        }


        $this->element[self::$formHolder[self::$formName]] = $elementString;

        return $this->element[self::$formHolder[self::$formName]];
    }

    //Error occured while using this method
    //Have to work on this.
    public function __toString()
    {
        return $this->element[@self::$formHolder[self::$formName]];
    }

    /**
     * @param $attributes
     * @return string
     */
    protected function attributes($attributes)
    {
        $element_str = "";

        foreach ($attributes as $key => $value) {
            $element_str .= "{$key}='{$value}' ";
        }

        return $element_str;
    }

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