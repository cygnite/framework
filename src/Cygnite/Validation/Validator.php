<?php
namespace Cygnite\Validation;

use Closure;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Input\Input;
use Cygnite\Foundation\Application;
use Cygnite\Validation\Exception\ValidatorException;

/**
 * Class Validator
 *
 * Validator use to validate user inputs
 *
 * @package Cygnite\Validation
 */
class Validator implements ValidatorInterface
{
    const ERROR = '.error';
    public $errors= [];
    public $columns = [];
    public $glue = PHP_EOL;
    protected $errorElementStart = '<span class="error">';
    protected $errorElementEnd = '</span>';
    /**
    * POST
    * @var array
    */
    private $param;
    protected $rules = [];
    public static $files = [];
    protected $validPhoneNumbers = [7,10,11];

    protected $after = [];

    /*
     * Constructor to set as protected.
     * You cannot create instance of validator directly
     *
     * set post array into param
     *
     * @param  $var post values
     *
     */
    protected function __construct($input)
    {
        if (!is_array($input)) {
            throw new ValidatorException(sprintf('Constructor expect array of input, %s given.', \gettype($key)));
        }

        $this->param = $input;
    }

    /*
     * Create validator to set rules
     * <code>
     *  $input = Input::make();
     *  $validator = Validator::create($input->post(), function ($validator)
     *  {
     *       $validator->addRule('username', 'required|min:3|max:5')
     *                 ->addRule('password', 'required|is_int|valid_date')
     *                 ->addRule('phone', 'phone|is_string')
     *                 ->addRule('email', 'valid_email');
     *
     *       return $validator;
     *   });
     *
     * </code>
     * @param  $var post values
     * @param  Closure callback
     * @return object
     */
    public static function create($var, Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new static($var));
        }

        return new static($var);
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
    * Add validation rule
    *
    * @param  $key
    * @param  $rule set up your validation rule
    * @return $this
    *
    */
    public function addRules(array $rules)
    {
        if (!is_array($rules) || empty($rules)) {
            throw new ValidatorException(sprintf('Validator::addRules() expect array of rules, %s given.', \gettype($rules)));
        }

        foreach ($rules as $field => $rule) {
            $this->addRule($field, $rule);
        }

        return $this;
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

    /**
     * Get error string
     *
     * <code>
     *   if ($validator->run()) {
     *       echo 'valid';
     *   } else {
     *       show($validator->getErrors());
     *   }
     * </code>
     * @param null $column
     * @return null|string
     */
    public function getErrors($column = null)
    {
        if (is_null($column)) {
            return $this->errors;
        }

        return isset($this->errors[$column.self::ERROR]) ? $this->errors[$column.self::ERROR] : null;
    }

    /**
     * Get error string
     *
     * <code>
     *   $validator->after(function($v) 
     *   {
     *       $v->setCustomError('field', 'Error Message!!');    
     *   });
     *   
     * </code>
     * @param $callback
     * @return $this Clouser Instance
     */
    public function after(\Closure $callback)
    {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
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
                    $isValid = $this->doValidateData($rule, $key, $isValid);
                } else {
                    $isValid = $this->doValidateMinMax($rule, $key, $isValid);
                }
            }
        }

        /*
        | We will fire all after validation callbacks
        | This is useful to override error message with 
        | custom messages
        */
        foreach ($this->after as $event) {
            call_user_func($event);
        }

        return $isValid;
    }

    /**
     * @param $rule
     * @param $key
     * @param $isValid
     * @return mixed
     * @throws \Exception
     */
    private function doValidateData($rule, $key, $isValid)
    {
        $method = Inflector::camelize($rule);

        if (!is_callable([$this, $method])) {
            throw new \Exception('Undefined method '.__CLASS__.' '.$method.' called.');
        }

        if ($isValid === false) {
            $this->setErrors($key.self::ERROR, Inflector::camelize((str_replace('_', ' ', $key))));
        }

        if (!isset($this->param[$key])) {
            throw new ValidatorException(sprintf('Key %s doesn\'t exists in $_POST array ', $key));
        }

        return $this->{$method}($key);
    }

    /**
     * @param $name
     * @param $value
     */
    private function setErrors($name, $value)
    {
        $this->columns[$name] =
            $this->errorElementStart.$value.' doesn\'t match validation rules'.$this->errorElementEnd;
    }

    /**
     * @param $rule
     * @param $key
     * @param $isValid
     * @return mixed
     * @throws \Exception
     */
    private function doValidateMinMax($rule, $key, $isValid)
    {
        $rule = explode(':', $rule);

        $method = Inflector::camelize($rule[0]);

        if (is_callable([$this, $method]) === false) {
            throw new \Exception('Undefined method '.__CLASS__.' '.$method.' called.');
        }

        if ($isValid === false) {
            $this->setErrors($key.self::ERROR, Inflector::camelize(str_replace('_', ' ', $key)));
        }

        return $this->$method($key, $rule[1]);
    }

    /*
    * Set required fields
    *
    * @param  $key
    * @return boolean true or false
    *
    */
    protected function required($key)
    {
        $val = trim($this->param[$key]);

        if (strlen($val) == 0) {
            $this->errors[$key.self::ERROR] =
                ucfirst($this->convertToFieldName($key)).' is required';
            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @return string
     */
    private function convertToFieldName($key)
    {
        return Inflector::underscoreToSpace($key);
    }

    /**
     * @param $key
     * @return bool
     */
    protected function validEmail($key)
    {
        $sanitize_email = filter_var($this->param[$key], FILTER_SANITIZE_EMAIL);

        if (filter_var($sanitize_email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$key.self::ERROR] = ucfirst($this->convertToFieldName($key)).' is not valid';
            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function isIp($key)
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

    /**
     * @param $key
     * @return bool
     */
    protected function isInt($key)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';

        if (isset($this->errors[$key.self::ERROR])) {
            list($conCate, $columnName) = $this->setErrorConcat($key);
        }

        if (filter_var($this->param[$key], FILTER_VALIDATE_INT) === false) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.' integer';
            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function isString($key)
    {
        $conCate =  '';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            list($conCate, $columnName) = $this->setErrorConcat($key);
        }

        $value = $this->param[$key];

        if (!is_string($value) || gettype($value) !== 'string') {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.'valid string';
            return false;
        }

        return true;
    }

    protected function isAlphaNumeric($key)
    {
        $conCate =  ' ';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';

        if (isset($this->errors[$key.self::ERROR])) {
            list($conCate, $columnName) = $this->setErrorConcat($key);
        }

        if (!ctype_alnum($this->param[$key])) {
            return $this->setAlphaNumError($key, $conCate, $columnName, 'alpha numeric.');
        }

        return true;
    }

    private function setErrorConcat($key)
    {
        $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and must be valid';
        $columnName = '';

        return [$conCate, $columnName];
    }

    private function setAlphaNumError($key, $conCate, $columnName, $func)
    {
        $this->errors[$key.self::ERROR] = trim($conCate.$columnName. $func);

        return false;
    }

    protected function isAlphaNumWithUnderScore($key)
    {
        $allowed = [".", "-", "_"];
        $columnName =  ucfirst($this->convertToFieldName($key)).' must be ';

        $conCate =  '';
        if (isset($this->errors[$key.self::ERROR])) {
            list($conCate, $columnName) = $this->setErrorConcat($key);
        }

        $string = str_replace($allowed, '', $this->param[$key]);

        if (!ctype_alnum($string)) {
            return $this->setAlphaNumError($key, $conCate, $columnName, 'alpha numeric with underscore/dash');
        }

        return true;
    }

    /**
     * @param $key
     * @param $length
     * @return bool
     */
    protected function min($key, $length)
    {
        $conCate = (isset($this->errors[$key.self::ERROR])) ?
            $this->errors[$key.self::ERROR].' and ' :
            '';

        //$stringLength = strlen($this->param[$key]);

        //if ($stringLength < (int) $length) {
        if (mb_strlen(trim($this->param[$key])) < $length) {
            $this->errors[$key.self::ERROR] =
                $conCate.ucfirst($this->convertToFieldName($key)).' should be '.__FUNCTION__.'imum '.$length.' characters.';

            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @param $length
     * @return bool
     */
    protected function max($key, $length)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (mb_strlen($this->param[$key]) <= $length) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.'maximum '.$length.' characters.';

            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    protected function validUrl($key)
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
     * Validate phone number
     *
     * @param $key
     * @return bool
     */
    protected function phone($key)
    {
        $num = preg_replace('/d+/', '', (int) $this->param[$key]);

        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (in_array(strlen($num), $this->validPhoneNumbers) == false) {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.'valid phone number.';
            return false;
        }

        return true;
    }

    /**
     * Validate date string
     *
     * @param $key
     * @return bool
     */
    public function validDate($key)
    {
        if ($this->param[$key] instanceof \DateTime) {
            return true;
        }

        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' is not';

        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = 'must be ';
        }

        $date = date_parse($this->param[$key]);
        $isDate = checkdate($date['month'], $date['day'], $date['year']);

        if (!$isDate) {
            $this->errors[$key.self::ERROR] = $conCate.$columnName.' valid date.';
        }

        return true;
    }

    /**
     * @return mixed
     */
    protected function files()
    {
        return static::$files = $_FILES;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function fileName($key)
    {
        $files = $this->files();

        return $files[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function isEmptyFile($key)
    {
        $conCate =  '';
        $columnName =  ucfirst($this->convertToFieldName($key)).' has ';

        $files = $this->fileName($key);

        if ($files['size'] == 0 && $files['error'] == 0) {
            $this->errors[$key.static::ERROR] = $conCate.$columnName.' empty file.';

            return false;
        }

        return true;
    }
}
