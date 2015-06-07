<?php
namespace Cygnite\Common\SessionManager;

abstract class AbstractPacket
{
    protected $storage = [];

    /**
     * Removes all data and reset the storage to empty array
     *
     * @return $this
     */
    public function reset()
    {
        $this->storage = [];

        return $this;
    }

    /**
     * Check if offset exists
     *
     * @param mixed $key
     * @return boolean true or false
     */
    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get value if exists from storage
     *
     * @param mixed $key
     * @return mixed Can return all value types.
     */
    public function &offsetGet($key)
    {
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = null;
        }
        return $this->storage[$key];
    }

    /**
     * Setting or pushing data into storage
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            array_push($this->storage, $value);
            return;
        }
        $this->storage[$key] = $value;
    }

    /**
     * Key to unset
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->storage[$key]);
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->storage);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->storage);
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        next($this->storage);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->storage);
    }

    /**
     * Checks if current position is valid and return bool value
     *
     * @return bool
     */
    public function valid()
    {
        $key = key($this->storage);
        if ($key === false || $key === null) {
            return false;
        }
        return isset($this->storage[$key]);
    }

}