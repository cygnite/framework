<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Foundation;

use Countable;
use ArrayAccess;
use Serializable;
use IteratorAggregate;
use BadMethodCallException;

class Collection implements Countable, IteratorAggregate, ArrayAccess, Serializable
{
    /**
     * The current result set as an array.
     *
     * @var array
     */
    protected $data = [];

    protected $serialize;

    /**
     * Optionally set the contents of the result set by passing in array.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Create a new collection instance with data.
     *
     * @param mixed $data
     * @return static
     */
    public static function create(array $data = []) : Collection
    {
        return new static($data);
    }

    /**
     * Set the contents of the result set by passing in array.
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the current result set as an array.
     *
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * Add additional arguments in the given collection.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function add(string $name, $value)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->data[$key] = $value;
            }

            return $this;
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * This is the identical method as add().
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        return $this->add($name, $value);
    }

    /**
     * Exchanges the current values with the input.
     *
     * @param mixed $array The values to exchange with
     *
     * @return array The old array
     */
    public function exchangeArray(array $array) : array
    {
        $oldValues = $this->data;
        $this->data = $array;

        return $oldValues;
    }

    /**
     * Alias method of getData.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->getData();
    }

    /**
     * Get the current result set as an array.
     *
     * @return array
     */
    public function asArray() : array
    {
        return $this->getData();
    }

    /**
     * Get the current result set as an array.
     *
     * @return array
     */
    public function asJson()
    {
        return json_encode($this->data);
    }

    /**
     * Get the number of records in the result set.
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->data);
    }

    /**
     * Get an iterator for this object. In this case it supports foreach
     * over the result set.
     *
     * @return \ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess.
     *
     * @param int|string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Serializable.
     *
     * @return string
     */
    public function serialize()
    {
        return $this->serialize = serialize($this->data);
    }

    /**
     * Un serialize the data.
     *
     * @param string $serialized
     *
     * @return array
     */
    public function unserialize($serialized = null)
    {
        if (is_null($serialized)) {
            return unserialize($this->serialize);
        }

        return unserialize($serialized);
    }

    /**
     * Filter over array element.
     *
     * @param \Closure|null $callback
     * @return Collection
     */
    public function filter(\Closure $callback = null) : Collection
    {
        if ($callback) {
            return static::create(array_filter($this->data, $callback));
        }

        return static::create(array_filter($this->data));
    }

    /**
     * Flip the array elements in the collection.
     *
     * @return Collection
     */
    public function flip() : Collection
    {
        return static::create(array_flip($this->data));
    }

    /**
     * Remove the item from collection.
     *
     * @param $key
     * @return Collection
     */
    public function remove(string $key) : Collection
    {
        $this->offsetUnset($key);

        return $this;
    }

    /**
     * Get an row from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Apply callback over each data element.
     *
     * @param \Closure $callback
     *
     * @return Collection
     */
    public function each(\Closure $callback) : Collection
    {
        array_map($callback, $this->data);

        return $this;
    }

    /**
     * Get keys from Collection object.
     *
     * @return Collection keys
     */
    public function keys() : Collection
    {
        return static::create(array_keys($this->data));
    }

    /**
     * Map array elements and return as Collection object.
     *
     * @param \Closure $callback
     * @return Collection
     */
    public function map(\Closure $callback) : Collection
    {
        $keys = array_keys($this->data);
        $values = array_map($callback, $this->data, $keys);

        return static::create(array_combine($keys, $values));
    }

    /**
     * Merge the collection with the given array.
     *
     * @param $data
     * @return Collection
     */
    public function merge($data) : Collection
    {
        return static::create(array_merge($this->data, $this->convertToArray($data)));
    }

    /**
     * Removes duplicate values from an array.
     * @return Collection
     */
    public function unique() : Collection
    {
        return static::create(array_unique($this->data));
    }

    /**
     * Sort each element with callback.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function sort(\Closure $callback) : Collection
    {
        uasort($this->data, $callback);

        return $this;
    }

    /**
     * Remove the first element from the collection array
     * and return Collection Instance.
     *
     * @return mixed|null
     */
    public function shift() : array
    {
        return array_shift($this->data);
    }

    /**
     *  Prepend one or more elements to the beginning of an array Collection.
     *
     * @param $element
     */
    public function prepend($element)
    {
        array_unshift($this->data, $element);
    }

    /**
     * Return first value of array if Collection not empty.
     *
     * @param null $default
     *
     * @return mixed|null
     */
    public function first($default = null)
    {
        return count($this->data) > 0 ? reset($this->data) : $default;
    }

    /**
     * Return first key of array if Collection not empty.
     *
     * @param null $default
     *
     * @return mixed|null
     */
    public function firstKey($default = null)
    {
        return count($this->data) > 0 ? key($this->data) : $default;
    }

    /**
     * Return last element of array if Collection not empty.
     *
     * @return mixed|null
     */
    public function last()
    {
        return count($this->data) != 0 ? end($this->data) : null;
    }

    /**
     * Reverse array elements.
     *
     * @return Collection
     */
    public function reverse() : Collection
    {
        return static::create(array_reverse($this->data));
    }

    /**
     * Searches the array for a given value
     * and return the key if found.
     *
     * @param      $element
     * @param bool $strict
     *
     * @return mixed
     */
    public function search($element, $strict = false)
    {
        return array_search($element, $this->data, $strict);
    }

    /**
     * Convert Collection Object as Array.
     *
     * @param $data
     *
     * @return array
     */
    public function convertToArray($data) : Collection
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        return $data;
    }

    /**
     * Return CachingIterator instance.
     *
     * @param int $flags
     *
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING) : \CachingIterator
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Check key exists in the Collection object.
     *
     * @param $key
     *
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Find if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        $collection = $this->all();

        return empty($collection);
    }

    /**
     * Call a method on all models in a result set. This allows for method
     * chaining such as setting a property on all models in a result set or
     * any other batch operation across models.
     *
     * @param string $method
     * @param array  $params
     *
     * @throws \BadMethodCallException
     *
     * @return $this
     */
    public function __call($method, $params = [])
    {
        foreach ($this->data as $class) {
            if (!method_exists($class, $method)) {
                throw new BadMethodCallException(
                    sprintf('Method %s() doesn\'t exists in class %s', $method, get_class($this))
                );
            }

            call_user_func_array([$class, $method], $params);
        }

        return $this;
    }
}
