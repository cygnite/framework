<?php
namespace Cygnite\Validation;

use Closure;
use Cygnite\Proxy\StaticResolver;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Input\Input;

interface ValidatorInterface
{
    public static function create($var, Closure $callback);

    public function addRule($key, $rule);

    public function validDate($key);

    public function notEmptyFile($key);

    public function setCustomError($key, $value);

    public function getErrors($column = null);

    public function run();
}