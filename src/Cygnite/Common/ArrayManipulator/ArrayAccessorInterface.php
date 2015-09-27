<?php
namespace Cygnite\Common\ArrayManipulator;

interface ArrayAccessorInterface
{
    /**
     * @param          $array
     * @param callable $callback
     * @return mixed
     */
    public static function make(array $array, \Closure $callback);

    /**
     * @param array $array
     * @return $this
     */
    public function set(array $array);

    /**
     * Return array
     *
     * @return array
     */
    public function getArray();


    /**
     * Check Array key Existence
     *
     * @param $key
     * @return mixed
     */
    public function has($key);

    /**
     * We will convert array to json objects
     *
     * @return string
     */
    public function toJson();

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function toString($key, $default = '');


    /**
     * @param        $key
     * @param string $default
     * @return mixed
     */
    public function toInt($key, $default = '');
}
