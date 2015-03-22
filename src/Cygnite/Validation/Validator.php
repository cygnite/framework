<?php
namespace Cygnite\Validation;

use Closure;
use Cygnite\Proxy\StaticResolver;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Input\Input;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
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
 * @Sub Packages              :  libraries
 * @Filename                  :  Validator
 * @Description               :  Validator library is to validate your form input fields.
 *
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link                      :  http://www.cygniteframework.com
 * @Since                     :  Version 1.0
 * @Filesource
 * @Warning                   :  Any changes in this library can cause abnormal behaviour of the framework
 *
 */
/*
 * @example
<code>
    $input = Input::getInstance(
            function ($instance) {
                return $instance;
            }
        );
    $validator = Validator::instance(
        $input,
        function ($validate) {
            $validate->addRule('username', 'required|min:3|max:5')
                ->addRule('password', 'required|is_int|valid_date')
                ->addRule('phone', 'phone|is_string')
                ->addRule('email', 'valid_email');

            return $validate;
        }
    );


    if ($validator->run()) {
        echo 'valid';
    } else {
        show($validator->getErrors());
    }
</code>
*/


class Validator
{
    /**
    * POST
    * @var array
    */
    private $param;

    private $rules = array();

    public $errors= array();

    public $columns = array();

    private $validPhoneNumbers = array(10,11,13,14,91,81);

    const ERROR = '_error';


    /*
     * Constructor to set as private.
     * You cannot create instance ob validator directly
     *
     * set post values into param array
     *
     * @param  $var post values
     *
     */
    private function __construct(Input $var)
    {
        if ($var instanceof Input) {
            $this->param = $var->post();
        }
    }

    /*
     * Get the validator instance with closure callback
     *
     * @param  $var post values
     * @param  Closure callback
     * @return object
     *
     */
    public static function instance($var, Closure $callback)
    {
        return $callback(new self($var));
    }

    /*
    * Add validation rule
    *
    * @param  $key
    * @param  $rule set up your validation rule
    * @return $this
    *
    */
    public function addRule($key, $rule)
    {
        $this->rules[$key] = $rule;

        return $this;
    }

    /*
    * Set required fields
    *
    * @param  $key
    * @return boolean true or false
    *
    */
    private function required($key)
    {
        $val = trim($this->param[$key]);

        if (strlen($val) == 0) {

            $this->errors[$key.self::ERROR] =
                ucfirst($this->convertToFieldName($key)).' is '.str_replace('_', ' ', __FUNCTION__);
            return false;
        }

        return true;
    }

    private function convertToFieldName($key)
    {
        return Inflector::underscoreToSpace($key);
    }

    private function validEmail($key)
    {
        $sanitize_email = filter_var($this->param[$key], FILTER_SANITIZE_EMAIL);

        if (filter_var($sanitize_email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$key.self::ERROR] = ucfirst($this->convertToFieldName($key)).' is not valid';
            return false;
        }

        return true;
    }


    private function isIp($key)
    {
        if (filter_var($this->param[$key], FILTER_VALIDATE_IP) === false) {
            $this->errors[$key.self::ERROR] =
                ucfirst($this->convertToFieldName($key)).' is not valid '.lcfirst(
                    str_replace('is', '', __FUNCTION__)
                );
            return false;
        }

        return true;
    }

    private function isInt($key)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';

        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and must be valid ';
            $columnName = '';
        }

        if (filter_var($this->param[$key], FILTER_VALIDATE_INT) === false) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.strtolower(str_replace('is', '', __FUNCTION__)).'ger.';
            return false;
        }

        return true;
    }

    private function isString($key)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and must be';
            $columnName = '';
        }

        $value = trim($this->param[$key]);

        if (is_string($value) == true && strlen($value) == 0) {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.' valid string';
            return false;
        }

        return true;
    }

    private function min($key, $length)
    {
        $conCate = (isset($this->errors[$key.self::ERROR])) ?
            $this->errors[$key.self::ERROR].' and ' :
            '';

        if (mb_strlen($this->param[$key]) >= $length) {
            return true;
        } else {
            $this->errors[$key.self::ERROR] =
                $conCate.ucfirst($this->convertToFieldName($key)).' should be '.str_replace(
                    '_',
                    ' ',
                    __FUNCTION__
                ).'mum '.$length.' characters.';

            return false;
        }
    }


    private function max($key, $length)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (mb_strlen($this->param[$key]) <= $length) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.__FUNCTION__.'mum '.$length.' characters.';

            return false;
        } else {
            return true;
        }
    }


    private function validUrl($key)
    {
        $sanitize_url = filter_var($this->param[$key], FILTER_SANITIZE_URL);

        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' is not a';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (filter_var($sanitize_url, FILTER_VALIDATE_URL) === false) {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.' valid url.';
            return false;
        }

        return true;
    }


    /**
     *
     * @param $key
     * @return bool
     */
    public function validDate($key)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = 'must be ';
        }

        if (strtotime($this->param[$key]) !== true) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.'valid date.';
        }

        return true;
    }

    /**
     * Validate phone number
     *
     * @param $key
     * @return bool
     */
    private function phone($key)
    {
        $num = preg_replace('#\d+#', '', $this->param[$key]);

        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' number is not ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (in_array(strlen($num), $this->validPhoneNumbers) == false) {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.'valid.';
        }

        return true;
    }


    public function notEmptyFile($key)
    {
        return empty($_FILES[$key]['name']) !== true;
    }

    private function setErrors($name, $value)
    {
        $this->columns[$name] = '<span class="error">'.$value.' doesn\'t match validation rules </span>';
    }

    /**
     * If you are willing to display custom error message
     * you can simple pass the field name with _error prefix and
     * set the message for it.
     *
     * @param $key
     * @param $value
     */
    public function setCustomError($key, $value)
    {
        $this->errors[$key] = $value;
    }

    public function getErrors($column = null)
    {
        if ($column === null) {
            return implode("<br />", array_values($this->errors));
        }

        return isset($this->errors[$column.self::ERROR]) ? $this->errors[$column.self::ERROR] : null;
    }

    /**
     * Run validation rules and catch errors
     *
     * @return bool
     * @throws \Exception
     */
    public function run()
    {
        $isValid = true;

        if (empty($this->rules)) {
            return true;
        }

        foreach ($this->rules as $key => $val) {

            $rules = explode('|', $val);

            foreach ($rules as $rule) {

                if (!strstr($rule, 'max') &&
                    !strstr($rule, 'min')
                ) {

                    $method = Inflector::camelize($rule);

                    if (is_callable(array($this, $method)) === false) {
                        throw new \Exception('Undefined method '.__CLASS__.' '.$method.' called.');
                    }
                    //echo $key."<br>";
                    if ($isValid === false) {
                        $this->setErrors($key.self::ERROR, Inflector::camelize((str_replace('_', ' ', $key))));
                    }

                    $isValid = $this->$method($key);
                    //$isValid = call_user_func(array($this, $rule[0]), array($key));

                } else {

                    $rule = explode(':', $rule);

                    $method = Inflector::camelize($rule[0]);

                    if (is_callable(array($this, $method)) === false) {
                        throw new \Exception('Undefined method '.__CLASS__.' '.$method.' called.');
                    }

                    if ($isValid === false) {
                        $this->setErrors($key.self::ERROR, Inflector::camelize(str_replace('_', ' ', $key)));
                    }

                    //$isValid = call_user_func_array(array($this, $rule[0]), array($key,$rule[1]));
                    $isValid = $this->$method($key, $rule[1]);
                }
            }
        }

        return $isValid;

    }
}
