<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Validation;

use Closure;
use Cygnite\Helpers\Inflector;
use Cygnite\Validation\Exception\ValidatorException;

/**
 * Class Validator.
 *
 * Validator use to validate user inputs
 */
class Validator implements ValidatorInterface
{
    const ERROR = '.error';
    protected $errors = [];
    protected $columns = [];
    public $glue = PHP_EOL;

    /**
     * POST $var array.
     */
    private $param;
    protected $rules = [];
    public static $files = [];
    protected $rulesExecuted = [];
    protected $validPhoneNumbers = [7, 10, 11];
    protected $errorElementEnd = '</span>';
    protected $errorElementStart = '<span class="error">';

    protected $after = [];

    /**
     * Constructor to set as protected.
     * You cannot create instance of validator directly
     *
     * set post array into param
     *
     * @param  $var post values
     *
     */
    protected function __construct(array $input)
    {
        if (!is_array($input)) {
            throw new ValidatorException(sprintf('Constructor expect array of input, %s given.', \gettype($input)));
        }

        $this->param = $input;
    }

    /**
     * Create validator to set rules
     * <code>
     *  $input = $request->post->all();
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
    public static function create(array $var, Closure $callback = null) : ValidatorInterface
    {
        if ($callback instanceof Closure) {
            return $callback(new static($var));
        }

        return new static($var);
    }

    /**
    * Add validation rule.
    *
    * @param  $key
    * @param  $rule set up your validation rule
    * @return $this
    *
    */
    public function addRule(string $key, string $rule) : ValidatorInterface
    {
        $this->rules[$key] = $rule;

        return $this;
    }

    /**
    * Add array of validation rule.
    *
    * @param  $key
    * @param  $rule set up your validation rule
    * @return $this
    *
    */
    public function addRules(array $rules) : ValidatorInterface
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
     * Get error string.
     *
     * <code>
     *   $validator->after(function($v)
     *   {
     *       $v->setCustomError('field', 'Error Message!!');
     *   });
     *
     * </code>
     *
     * @param $callback
     *
     * @return $this Clouser Instance
     */
    public function after(\Closure $callback) : ValidatorInterface
    {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
    }

    /**
     * Run validation rules and catch errors.
     *
     * @throws \Exception
     * @return bool
     */
    public function run()
    {
        $isValid = true;
        if (empty($this->rules)) {
            return true;
        }

        $this->rulesExecuted = [];

        foreach ($this->rules as $key => $val) {
            $rules = explode('|', $val);

            foreach ($rules as $rule) {

                // Executes rule for min and max validation.
                if (string_has($rule, ':') && strstr($rule, 'max') || strstr($rule, 'min')) {
                    $isValid = $this->doValidateMinMax($rule, $key, $isValid);
                } elseif (string_has($rule, ':') && (!strstr($rule, 'max') && !strstr($rule, 'min'))) {
                    // Executes rule for other than min, max validation with ":" keyword in rule.
                    $isValid = $this->validateRulesHasPlaceHolder($key, $rule, $isValid);
                } else {
                    // Executes all other rules.
                    $isValid = $this->doValidateData($rule, $key, $isValid);
                }

                $this->rulesExecuted[] = $isValid;
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

        // We will return false if any one of the validation rules failed.
        if (in_array(false, $this->rulesExecuted)) {
            return false;
        }

        return true;
    }

    /**
     * Validate input against rules contains placeholder.
     *
     * @param $key
     * @param $rule
     * @param $isValid
     * @return mixed
     */
    private function validateRulesHasPlaceHolder($key, $rule, $isValid)
    {
        $string = string_split($rule, ':');

        if (!string_has($string[1], ',')) {
            return $this->doValidateData($string[0], $string[1], $isValid, $key);
        }

        return $this->doValidateData($string[0], $key, $isValid, $string[1]);
    }

    /**
     * Do validate user data.
     *
     * @param $rule
     * @param $key
     * @param $isValid
     * @param string $other
     * @return mixed
     * @throws ValidatorException
     * @throws \Exception
     */
    private function doValidateData($rule, $key, $isValid, $other = '')
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

        return (!empty($other)) ? $this->{$method}($key, $other) : $this->{$method}($key);
    }

    /**
     * Validate min and max input.
     *
     * @param $rule
     * @param $key
     * @param $isValid
     * @throws \Exception
     * @return mixed
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
    * Set required fields.
    *
    * @param  $key
    * @return boolean true or false
    *
    */
    protected function required(string $key) : bool
    {
        $val = $this->param[$key];

        if (is_string($val) && strlen(trim($val)) == 0) {
            $this->errors[$key.self::ERROR] =
                ucfirst($this->convertToFieldName($key)).' is required';

            return false;
        } elseif (is_array($val) && count($val) < 1) {
            $this->errors[$key.self::ERROR] =
                ucfirst($this->convertToFieldName($key)).' is required';

            return false;
        }

        return true;
    }

    /**
     * @param $key
     *
     * @return string
     */
    private function convertToFieldName(string $key)
    {
        return Inflector::underscoreToSpace($key);
    }


    /**
     * @param $key
     * @return bool
     */
    protected function validEmail(string $key) : bool
    {
        $sanitize_email = filter_var($this->param[$key], FILTER_SANITIZE_EMAIL);

        if (filter_var($sanitize_email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$key.self::ERROR] = ucfirst($this->convertToFieldName($key)).' is not valid';

            return false;
        }

        return true;
    }

    /**
     * Check if given input is IP address.
     *
     * @param $key
     * @return bool
     */
    protected function isIp($key) : bool
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
     * Check if given input is integer.
     *
     * @param $key
     * @return bool
     */
    protected function isInt($key) : bool
    {
        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';

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
     * Check if given input is string.
     *
     * @param $key
     * @return bool
     */
    protected function isString(string $key) : bool
    {
        $conCate = '';
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

    /**
     * Check if given input alpha numeric.
     *
     * @param $key
     * @return bool
     */
    protected function isAlphaNumeric($key) : bool
    {
        $conCate = ' ';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';

        if (isset($this->errors[$key.self::ERROR])) {
            list($conCate, $columnName) = $this->setErrorConcat($key);
        }

        if (!ctype_alnum($this->param[$key])) {
            return $this->setAlphaNumError($key, $conCate, $columnName, 'alpha numeric.');
        }

        return true;
    }

    /**
     * @param $key
     * @return array
     */
    private function setErrorConcat($key)
    {
        $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and must be valid';
        $columnName = '';

        return [$conCate, $columnName];
    }

    /**
     * @param $key
     * @param $conCate
     * @param $columnName
     * @param $func
     * @return bool
     */
    private function setAlphaNumError($key, $conCate, $columnName, $func)
    {
        $this->errors[$key.self::ERROR] = trim($conCate.$columnName.$func);

        return false;
    }

    /**
     * Check if given input is alphanumeric with underscore.
     *
     * @param $key
     * @return bool
     */
    protected function isAlphaNumWithUnderScore($key)
    {
        $allowed = ['.', '-', '_'];
        $columnName = ucfirst($this->convertToFieldName($key)).' must be ';

        $conCate = '';
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
     * Validate for minimum character.
     *
     * @param $key
     * @param $length
     *
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
     * Validate given input for maximum character.
     *
     * @param $key
     * @param $length
     *
     * @return bool
     */
    protected function max($key, $length)
    {
        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';
        if (isset($this->errors[$key.self::ERROR])) {
            $conCate = str_replace('.', '', $this->errors[$key.self::ERROR]).' and ';
            $columnName = '';
        }

        if (mb_strlen($this->param[$key]) <= $length) {
            $this->errors[$key.self::ERROR] =
                $conCate.$columnName.'maximum '.$length.' characters.';

            return false;
        }

        return true;
    }

    /**
     * Validate given input is matching url type.
     *
     * @param $key
     * @return bool
     */
    protected function validUrl(string $key)
    {
        $sanitize_url = filter_var($this->param[$key], FILTER_SANITIZE_URL);

        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' is not a';
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
     * Validate phone number.
     *
     * @param $key
     * @return bool
     */
    protected function phone($key) : bool
    {
        $num = preg_replace('/d+/', '', (int) $this->param[$key]);

        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' should be ';
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
     * Validate date string.
     *
     * @param $key
     * @return bool
     */
    public function validDate($key) : bool
    {
        if ($this->param[$key] instanceof \DateTime) {
            return true;
        }

        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' is not';

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
     *
     * @return mixed
     */
    protected function fileName($key)
    {
        $files = $this->files();

        return $files[$key];
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isEmptyFile($key)
    {
        $conCate = '';
        $columnName = ucfirst($this->convertToFieldName($key)).' has ';

        $files = $this->fileName($key);

        if ($files['size'] == 0 && $files['error'] == 0) {
            $this->errors[$key.static::ERROR] = $conCate.$columnName.' empty file.';

            return false;
        }

        return true;
    }

    /**
     * Validate if given input is present in given rules.
     *
     * @param $key
     * @return bool
     */
    protected function isPresent(string $key) : bool
    {
        if (!array_key_exists($key, $this->param)) {
            $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' not exists in given inputs.';
            return false;
        }

        return true;
    }

    /**
     * Validate if given input value is array.
     *
     * @param $key
     * @return bool
     */
    protected function isArray($key) : bool
    {
        if (is_array($this->param[$key]) && !$this->hasAttribute($key)) {
            $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' value must be valid array.';
            return false;
        }

        return true;
    }

    /**
     * Validate if given input is matching with other field.
     *
     * @param $key
     * @param $field
     * @return bool
     */
    protected function isSame(string $key, string $field) : bool
    {
        $other = $this->param[$key];

        if (isset($other) && $field === $other) {
            return true;
        }

        $this->errors[$field.self::ERROR] = $this->convertToFieldName($field).' value must be same as '.
            $this->convertToFieldName($key).'.';

        return false;
    }

    /**
     * Verify if given input is matching with other field.
     * This method mostly used for password and password_confirm
     * field to validate both value are same.
     *
     * @param $key
     * @param $field
     * @return bool
     */
    protected function isConfirmed(string $key, string $field) : bool
    {
        return $this->isSame($key, $field.'_confirm');
    }

    /**
     * Verify if given input is available in 'yes', 'on', '1', 1, true, 'true'.
     *
     * @param $key
     * @return bool
     */
    protected function isAccepted(string $key) : bool
    {
        $allowed = ['yes', 'on', '1', 1, true, 'true'];

        if ($this->required($key) && in_array($this->param[$key], $allowed, true)) {
            return true;
        }

        $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' must be yes, on, 1, or true.';

        return false;
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function hasAttribute($attribute) : bool
    {
        return in_array($attribute, $this->param);
    }

    /**
     * Validate if given input is boolean.
     *
     * @param $key
     * @return bool
     */
    protected function isBool($key) : bool
    {
        $allowed = [true, false, 0, 1, '0', '1'];

        if (isset($this->param[$key]) && in_array(strtolower($this->param[$key]), $allowed, true)) {
            return true;
        }

        $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' must be boolean.';

        return false;
    }

    /**
     * Verify if given input is date.
     *
     * @param $key
     * @return bool
     */
    protected function isDate($key) : bool
    {
        $value = $this->param[$key];
        if (isset($value) && $value instanceof \DateTime) {
            return true;
        }

        $date = date_parse($value);

        if ((!is_string($value) && !is_numeric($value)) || strtotime($value) === false) {
            $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' must be a valid date.';
            return false;
        } elseif (checkdate($date['month'], $date['day'], $date['year'])) {
            return true;
        }


        $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' must be a valid date.';
        return false;
    }

    /**
     * Check if given input is timezone.
     *
     * @param $key
     * @return bool
     */
    protected function validTimezone($key) : bool
    {
        try {
            new \DateTimeZone($this->param[$key]);
        } catch (\Exception $e) {
            $this->errors[$key.self::ERROR] = $this->convertToFieldName($key).' must be a valid timezone.';
            return false;
        }

        return true;
    }

    /**
     * Validate if given digit is in between given min and max value in rules.
     *
     * @param $key
     * @param $value
     * @return bool
     */
    protected function isBetween($key, $value) : bool
    {
        $data = string_split($value, ',');
        $min = (int) $data[0];
        $max = (empty($data[1])) ? PHP_INT_MAX : (int) $data[1];
        $num = $this->param[$key];

        if (!is_numeric($num)) {
            $this->errors[$key.self::ERROR] = $this->convertToFieldName($key)." must numeric.";
            return false;
        }

        if ($num > $min && $num < $max) {
            return true;
        }

        $this->errors[$key.self::ERROR] = $this->convertToFieldName($key)." must have a length between the given $min and $max.";
        return false;
    }

    /**
     * Validate is given value is available in given rule.
     *
     * @param $key
     * @param $value
     * @return bool
     */
    protected function isIn($key, $value) : bool
    {
        $data = explode(',', $value);
        if (is_array($data) && in_array((string) $this->param[$key], $data)) {
            return true;
        }

        $this->errors[$key.self::ERROR] = $this->convertToFieldName($key)." must be in the given list of values $value.";
        return false;
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
     * If you are willing to display custom error message
     * you can simple pass the field name with _error prefix and
     * set the message for it.
     *
     * @param $key
     * @param $value
     */
    public function setCustomError($key, $value) : ValidatorInterface
    {
        $this->errors[$key] = $value;

        return $this;
    }

    /**
     * Get error strings.
     *
     * <code>
     *   if ($validator->run()) {
     *       //Valid request
     *   } else {
     *       show($validator->getErrors());
     *   }
     * </code>
     *
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
     * Check if validation error exists for particular input element.
     *
     * @param $key
     * @return bool
     */
    public function hasError($key) : bool
    {
        return isset($this->errors[$key.self::ERROR]) ? true : false;
    }
}
