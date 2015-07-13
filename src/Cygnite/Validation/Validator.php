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
    const ERROR = '_error';
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
    private $rules = [];
    private $validPhoneNumbers = [10,11,13,14,91,81];

    /*
     * Constructor to set as protected.
     * You cannot create instance of validator directly
     *
     * set post array into param
     *
     * @param  $var post values
     *
     */
    public function __construct(Input $input)
    {
        if (!$input instanceof Input) {
            throw new ValidatorException(sprintf('Constructor expects Input instance, give %s ', $input));
        }

        $this->param = $input->post();
    }

    /*
     * Create validator to set rules
     * <code>
     *  $input = Input::make();
     *  $validator = Validator::create($input, function ($validator)
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
            return implode($this->glue, array_values($this->errors));
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
                    $isValid = $this->doValidateData($rule, $key, $isValid);
                    //$isValid = call_user_func([$this, $rule[0]], [$key]);
                } else {
                    $isValid = $this->doValidateMinMax($rule, $key, $isValid);
                }
            }
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
                ucfirst($this->convertToFieldName($key)).' is '.str_replace('_', ' ', __FUNCTION__);
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

    /**
     * @param $key
     * @return bool
     */
    protected function isString($key)
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
                $conCate.$columnName.__FUNCTION__.'mum '.$length.' characters.';

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
     * @param $key
     * @return bool
     */
    public function notEmptyFile($key)
    {
        return empty($_FILES[$key]['name']) !== true;
    }
}
