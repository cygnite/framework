<?php
namespace Cygnite\Validation;

use Closure;

interface ValidatorInterface
{
    /**
     * @param          $var
     * @param callable $callback
     * @return mixed
     */
    public static function create($var, Closure $callback);

    /**
     * @param $key
     * @param $rule
     * @return mixed
     */
    public function addRule($key, $rule);

    /**
     * @param $key
     * @return mixed
     */
    public function validDate($key);

    /**
     * @param $key
     * @return mixed
     */
    public function notEmptyFile($key);

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setCustomError($key, $value);

    /**
     * @param null $column
     * @return mixed
     */
    public function getErrors($column = null);

    /**
     * Run validation
     * @return mixed
     */
    public function run();
}
