<?php
namespace Cygnite\Foundation;

use Countable;
use ArrayAccess;
use Serializable;
use IteratorAggregate;
use BadMethodCallException;

class Collection implements Countable, IteratorAggregate, ArrayAccess, Serializable
{
    /**
     * The current result set as an array
     * @var array
     */
    protected $data = [];

    /**
     * Optionally set the contents of the result set by passing in array
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Create a new collection instance with data.
     *
     * @param  mixed $data
     * @return static
     */
    public static function create($data = [])
    {
        return new static($data);
    }

    /**
     * Set the contents of the result set by passing in array
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the current result set as an array
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Alias method of getData
     *
     * @return array
     */
    public function all()
    {
        return $this->getData();
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function asArray()
    {
        return $this->getData();
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function asJson()
    {
        return json_encode($this->getData());
    }

    /**
     * Get the number of records in the result set
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Get an iterator for this object. In this case it supports foreach
     * over the result set.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * ArrayAccess
     * @param  int|string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess
     * @param  int|string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Serializable
     * @return string
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * Serializable
     * @param  string $serialized
     * @return array
     */
    public function unserialize($serialized)
    {
        return unserialize($serialized);
    }

    /**
     * Filter over array element
     *
     * @param  \Closure|null $callback
     * @return static
     */
    public function filter(\Closure $callback = null)
    {
        if ($callback) {
            return static::create(array_filter($this->data, $callback));
        }

        return static::create(array_filter($this->data));
    }

    /**
     * Flip the array elements in the collection.
     *
     * @return static
     */
    public function flip()
    {
        return static::create(array_flip($this->data));
    }

    /**
     * @param $key
     * @return $this
     */
    public function remove($key)
    {
        $this->offsetUnset($key);

        return $this;
    }

    /**
     * Get an row from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Get keys from Collection object
     *
     * @return static keys
     */
    public function keys()
    {
        return static::create(array_keys($this->data));
    }

    /**
     * Map array elements and return as Collection object
     *
     * @param  \Closure $callback
     * @return static
     */
    public function map(\Closure $callback)
    {
        $keys = array_keys($this->data);
        $values = array_map($callback, $this->data, $keys);

        return static::create(array_combine($keys, $values));
    }

    /**
     * Return CachingIterator instance.
     *
     * @param  int  $flags
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING)
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Check key exists in the Collection object
     *
     * @param $key
     * @return bool
     */
    public function has($key)
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
        return empty($this->all());
    }

    /**
     * Call a method on all models in a result set. This allows for method
     * chaining such as setting a property on all models in a result set or
     * any other batch operation across models.
     *
     * @param  string $method
     * @param  array  $params
     * @throws \BadMethodCallException
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
