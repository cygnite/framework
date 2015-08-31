<?php
namespace Cygnite\Foundation;

use Countable;
use ArrayAccess;
use Serializable;
use IteratorAggregate;
use Cygnite\Database\Exceptions\ActiveRecordMethodMissingException;

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
     * Set the contents of the result set by passing in array
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function getData()
    {
        return $this->data;
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
     * Call a method on all models in a result set. This allows for method
     * chaining such as setting a property on all models in a result set or
     * any other batch operation across models.
     *
     * @param  string   $method
     * @param  array    $params
     * @throws ActiveRecordMethodMissingException
     * @return dataset
     */
    public function __call($method, $params = [])
    {
        foreach ($this->data as $model) {
            if (method_exists($model, $method)) {
                call_user_func_array([$model, $method], $params);
            } else {
                throw new ActiveRecordMethodMissingException("Method $method() does not exist in class " . get_class($this));
            }
        }

        return $this;
    }
}
