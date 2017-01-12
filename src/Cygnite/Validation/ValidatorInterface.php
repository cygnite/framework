<?php

namespace Cygnite\Validation;

use Closure;

interface ValidatorInterface
{
    /**
     * @param          $var
     * @param callable $callback
     *
     * @return mixed
     */
    public static function create(array $var, Closure $callback);

    /**
     * @param $key
     * @param $rule
     *
     * @return mixed
     */
    public function addRule(string $key, string $rule) : ValidatorInterface;

    /**
     * Add array of validation rule.
     *
     * @param  $key
     * @param  $rule set up your validation rule
     * @return $this
     *
     */
    public function addRules(array $rules) : ValidatorInterface;

    /**
     * @param $key
     *
     * @return mixed
     */
    public function validDate($key);

    /**
     * @param $key
     *
     * @return mixed
     */
    public function isEmptyFile($key);

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function setCustomError($key, $value);

    /**
     * @param null $column
     *
     * @return mixed
     */
    public function getErrors($column = null);

    /**
     * Check if validation error exists for particular input element.
     *
     * @param $key
     * @return bool
     */
    public function hasError($key) : bool;

    /**
     * Run validation.
     *
     * @return mixed
     */
    public function run();
}
