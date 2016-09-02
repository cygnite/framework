<?php

namespace Cygnite\Common\ArrayManipulator;

class ArrayAccessor implements ArrayAccessorInterface
{
    protected $arrayStack = [];

    protected $replaceWith = '_REPLACEMENT_';

    protected $identifierArray = ['_', '-'];

    protected $defaultIdentifier = '.';

    /**
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->set($array);
    }

    /**
     * Get the instance of ArrayAccessor.
     *
     * @param array         $array
     * @param callable|null $callback
     *
     * @return mixed
     */
    public static function make(array $array, \Closure $callback = null)
    {
        if ($callback instanceof \Closure) {
            return $callback(new static($array));
        }

        return new static($array);
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public function set(array $array)
    {
        $this->arrayStack = $array;

        return $this;
    }

    /**
     * @param string $string
     *
     * @return array
     */
    protected function getKeysFromString($string)
    {
        $string = str_replace($this->identifierArray, $this->replaceWith, $string);
        $parts = explode($this->defaultIdentifier, $string);

        return array_map(
            function ($part) {
                return str_replace($this->replaceWith, $this->defaultIdentifier, $part);
            },
            $parts
        );
    }

    /**
     * We will manipulate the string to get each array index
     * and find array value.
     *
     * @param string $string
     *
     * @return mixed
     */
    protected function manipulate($string)
    {
        $chunks = $this->getKeysFromString($string);
        $array = $this->getArray();

        return $this->formatArray($array, $chunks);
    }

    /**
     * Check Array key Existence.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->keyExists($key, $this->getArray());
    }

    /**
     * Determine if array key exists from multi-dimensional array.
     *
     * @param $key
     * @param $array
     *
     * @return bool
     */
    public function keyExists($key, $array)
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach ($array as $k => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (array_key_exists($key, $value)) {
                return true;
            }
        }

        return false;
    }

    private function formatArray($array, $chunks)
    {
        /*
         | Loop all array index to find the array value
         */
        foreach ($chunks as $index) {
            if (!isset($array[$index])) {
                return;
            }

            $array = $array[$index];
        }

        return $array;
    }

    /**
     * Return array.
     *
     * @return array
     */
    public function getArray()
    {
        return $this->arrayStack;
    }

    /**
     * We will convert array to json objects.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->getArray());
    }

    /**
     * Return value as string.
     *
     * @param        $key
     * @param string $default
     *
     * @return string
     */
    public function toString($key, $default = '')
    {
        $value = $this->manipulate($key);

        return $this->convertAs('strval', $value, $default);
    }

    /**
     * Return value as string.
     *
     * @param        $key
     * @param string $default
     *
     * @return int
     */
    public function toInt($key, $default = '')
    {
        $value = $this->manipulate($key);

        return $this->convertAs('intval', $value, $default);
    }

    /**
     * @param $func
     * @param $value
     * @param $default
     *
     * @return mixed
     */
    private function convertAs($func, $value, $default)
    {
        /*
         | If we don't find array index we will return default value
         */
        if (is_null($value)) {
            return $func($default);
        }

        return $func($value);
    }
}
